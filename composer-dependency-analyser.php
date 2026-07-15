<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration;

return $config
    // orchestra/testbench is a metapackage that installs orchestra/testbench-core.
    // The codebase uses classes from testbench-core, which composer-dependency-analyser
    // flags as a shadow dependency while marking orchestra/testbench as unused.
    ->ignoreErrorsOnPackage('orchestra/testbench', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('orchestra/testbench-core', [ErrorType::SHADOW_DEPENDENCY])
    // Since we run in a multi-version Laravel CI matrix, some versions might not trigger
    // these ignores (e.g. Testbench 11 uses a different class structure), which throws
    // a "Some ignored issues never occurred" error. We disable reporting for unmatched ignores.
    ->disableReportingUnmatchedIgnores();
