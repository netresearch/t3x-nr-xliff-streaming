# Performance Test Fixtures

This directory contains large XLIFF files for performance testing.

## Generating Test Files

Run the generator script to create test files:

```bash
ddev exec php Build/scripts/generate-xliff-samples.php
```

This will generate:
- sample-30kb.xlf (~40 KB, 100 trans-units)
- sample-1mb.xlf (~1.2 MB, 3,000 trans-units)
- sample-30mb.xlf (~40 MB, 100,000 trans-units)
- sample-100mb.xlf (~130 MB, 330,000 trans-units)

## Running Benchmarks

```bash
ddev exec php Build/scripts/run-performance-benchmark.php
```

See PERFORMANCE_BENCHMARK.md for detailed results.

