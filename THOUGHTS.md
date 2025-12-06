# Stream Reading Improvements for CI Testing

## Problem Statement
Tests fail on CI (GitHub Actions) because `stream_select` behavior differs from local environment. The REPL process outputs to stdout, but tests only capture the banner and miss command output. This is likely due to:
- GitHub Actions TTY limitations
- Stderr writes that unblock select
- Different buffering behavior in CI

## Core Considerations

### 1. ✅ Fix the Loop Logic (DONE)
**Problem**: Current loop exits on first timeout without a prompt, even with longer timeouts.
**Solution**: Continue polling on timeout instead of breaking. Use overall timeout guard.
**Status**: Implemented in `ReplOutputReader::readOutput()`
- Changed `break` to `continue` on timeout without prompt
- Added overall timeout tracking

### 2. ✅ Environment Configuration (DONE)
**Solution**: Use .env for local, .env.test for CI with configurable timeouts
**Status**: Implemented
- `.env` with 1.5s timeout (local, gitignored)
- `.env.test` with 5.0s timeout (CI, tracked)
- ReplOutputReader reads from environment

### 3. ✅ Read from Both stdout AND stderr (DONE)
**Problem**: GitHub Actions might write to stderr, causing stream_select to unblock, but we only read stdout.
**Solution**:
- Pass both `$pipes[1]` (stdout) and `$pipes[2]` (stderr) to stream_select
- Read from whichever is ready
- Only append stdout to output (discard stderr noise, or log it)
**Status**: IMPLEMENTED in `ReplOutputReader::readOutput()`
- Modified signature to accept `$stderrStream` parameter
- Loop reads from both streams
- stderr output logged but not included in return value
- Updated `ReplSteps::readOutput()` to pass stderr

### 4. ✅ Debug Logging for CI (DONE)
**Problem**: Can't see what's happening in CI without visibility, but don't want noise on passing tests
**Solution**: Add conditional debug logging to ReplOutputReader
**Status**: IMPLEMENTED
- Buffers log messages instead of immediately outputting
- Only outputs logs when:
  - `REPL_DEBUG=true` in .env (always log)
  - Timeout occurs with no output (indicates failure)
  - stream_select error occurs
- Keeps local test output clean (.env has `REPL_DEBUG=false`)
- CI gets full logs (.env.test has `REPL_DEBUG=true`)
- Added to `ReplProcessManager::sendInput()` as well

### 5. ✅ Process Management Review (DONE)
**Current**: Using `proc_open` with pipes, streams set to non-blocking
**Considerations**:
- After writing to stdin (`$pipes[0]`), explicitly `fflush($pipes[0])`
- Consider `fclose($pipes[0])` after all input to signal EOF (may not be appropriate for REPL)
- Ensure both stdout and stderr are non-blocking
**Status**: REVIEWED - Already doing `fflush()` after writes
- Added logging to `sendInput()` to verify bytes written

### 6. 🔮 Sentinel/Test Mode Approach (FUTURE)
**Alternative**: Instead of guessing prompts, have REPL print a sentinel in test mode
```php
// In test mode, REPL prints:
echo "COMMAND_DONE_MARKER\n";
```
Then look for sentinel instead of prompt patterns.
**Status**: NOT IMPLEMENTED - Requires REPL changes
**Priority**: LOW - Nice to have, but invasive

### 7. 🔮 Phunkie Streams Solution (FUTURE)
**Idea**: Extract this into a reusable Phunkie Streams component
- `Stream\Process\asyncRead($stream, $predicate, $timeout)`
- Handles non-blocking reads, multiple streams, sentinel detection
**Status**: NOT IMPLEMENTED
**Priority**: LOW - After we solve the immediate problem

## Action Items (Prioritized)

### Immediate (Baby Steps)
1. ✅ Fix loop to continue polling instead of breaking on timeout
2. ✅ Add .env configuration for timeouts
3. ✅ Modify `ReplOutputReader::readOutput()` to accept stderr stream
4. ✅ Update `ReplSteps` to pass stderr to `readOutput()`
5. ✅ Read from both streams in the loop, only append stdout to output
6. ✅ Add debug logging (error_log) to see what's happening in CI
7. ✅ Review `ReplProcessManager` for proper fflush() usage
8. ⏳ **NEXT**: Test locally to verify changes don't break existing tests
9. ⏳ **NEXT**: Test on CI with full test suite

### Long Term
10. Review CI logs to see if stderr reading solves the issue
11. Consider reducing timeouts if tests are passing consistently
12. Consider sentinel-based approach for more reliable testing
13. Extract pattern into Phunkie Streams if successful

## Notes
- **Don't just increase timeouts** - that makes CI slow and doesn't address root cause
- **Stderr reading is likely the key** - GitHub Actions might be writing to stderr
- **Keep local tests fast** - use .env for short timeouts locally
- **Baby steps** - implement one thing at a time and test

## Current Status
- Loop logic fixed ✅
- Environment config added ✅
- Stderr reading implemented ✅
- Debug logging implemented with buffering ✅
- **SENTINEL APPROACH IMPLEMENTED** ✅
- **LOCAL TESTS**: All 418 scenarios, 2056 steps PASSING in ~18s ✅
- **NEXT**: Test on CI to verify sentinel approach works in GitHub Actions

## Test Results
- **Local (PHP 8.4)**: 418 scenarios, 2056 steps - ALL PASSED ✅
- **CI**: Pending - need to push and test

## Sentinel Approach + Blocking Read Strategy

### The Problem
GitHub Actions has different TTY/buffering behavior than local environments. The original non-blocking, short-timeout polling approach with `stream_select` would break too aggressively on timeouts, exiting the read loop before all data arrived. This caused tests to only capture the REPL banner, missing actual command output.

### The Solution: Two-Part Fix

#### Part 1: Sentinel Marker (Explicit Ready Signal)
Instead of guessing when the REPL is ready by detecting prompt patterns, the REPL explicitly prints `__PHUNKIE_READY__` when ready for input.

**REPL side** (`src/Repl/ReplLoop.php`):
- Added `isTestMode()` to check `REPL_TEST_MODE` environment variable
- Added `printTestSentinel()` that prints `__PHUNKIE_READY__\n` in test mode
- Called before each prompt in `replLoopTrampoline()`

**Test side**:
- `ReplProcessManager` loads `.env` and passes `REPL_TEST_MODE` to child process
- `ReplOutputReader` detects sentinel instead of prompt patterns in test mode
- Sentinel is stripped from output before returning to tests
- `ReplSteps.waitForPrompt()` uses `ReplOutputReader` for consistent detection

#### Part 2: Blocking Read Strategy (Patience Over Speed)
Changed from aggressive timeout-based polling to blocking reads that wait naturally for data.

**Key changes in `ReplOutputReader::readOutput()`**:
1. **Longer `stream_select` timeout**: Up to 1 second (vs 50ms) to let data arrive naturally
2. **No early exits on timeout**: Only breaks when:
   - Prompt/sentinel found ✅
   - Overall timeout hit ⏱️
   - EOF/error encountered ❌
3. **Calculated remaining timeout**: Uses `min(1.0, remainingTimeout)` for smart waiting
4. **Smaller read chunks**: 4096 bytes for more incremental reading
5. **Proper error handling**: Detects EOF and read errors explicitly

**Before (aggressive)**:
```php
// 50ms timeout on stream_select
$result = @stream_select($read, $write, $except, 0, 50000);
if ($result === 0) {
    break; // ❌ Exits too early!
}
```

**After (patient)**:
```php
// Up to 1 second timeout, calculated from remaining time
$selectTimeout = min(1.0, $remainingTimeout);
$result = @stream_select($read, $write, $except, $selectTimeoutSec, $selectTimeoutUsec);
if ($result === 0) {
    if (self::endsWithPrompt($output)) {
        break; // ✅ Only exit if we have complete response
    }
    continue; // ✅ Keep waiting for data
}
```

### Benefits
- ✅ **Reliable in CI**: Doesn't break early when I/O is slow
- ✅ **Explicit ready signal**: Sentinel removes guesswork
- ✅ **Fast locally**: ~18 seconds for 418 scenarios
- ✅ **Handles slow environments**: Waits patiently up to overall timeout
- ✅ **Clean output**: Sentinel stripped automatically
- ✅ **Proper error handling**: Detects EOF and errors gracefully

### Configuration
- `.env` (local): `REPL_TEST_MODE=true`, `REPL_OUTPUT_TIMEOUT=1.5`
- `.env.test` (CI): `REPL_TEST_MODE=true`, `REPL_OUTPUT_TIMEOUT=5.0`

### Files Modified
- `src/Repl/ReplLoop.php` - Sentinel printing
- `tests/Acceptance/Support/ReplProcessManager.php` - Environment passing
- `tests/Acceptance/Support/ReplOutputReader.php` - **Blocking read strategy + sentinel detection**
- `tests/Acceptance/ReplSteps.php` - Use ReplOutputReader in test mode
- `.env` and `.env.test` - Configuration
