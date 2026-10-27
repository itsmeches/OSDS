# Before-notification measurements

The original `*-frames.json` files preserve computed CSS colors, ancestor opacity, classes and timing from actual evaluator actions. Their initial derived numeric color/contrast fields did not support OKLCH and must not be used for contrast conclusions.

Use `*-normalized-frames.json` and `normalized-results.json`. These convert the retained computed colors through Chrome canvas to sRGB, recalculate composited contrast and record the original input hashes. Original raw files were not overwritten. The remediation report explains the correction.
