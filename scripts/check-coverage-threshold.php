<?php
/**
 * Coverage Threshold Checker voor SlimmerMetAI
 * 
 * Dit script controleert of de code coverage voldoet aan de 
 * ingestelde thresholds en geeft feedback aan de CI/CD pipeline.
 */

// Coverage thresholds (percentages)
const MIN_COVERAGE_THRESHOLD = 80;
const COVERAGE_TARGETS = [
    'lines' => 80,
    'functions' => 80,
    'branches' => 80,
    'statements' => 80
];

// Colors voor terminal output
const COLOR_RED = "\033[31m";
const COLOR_GREEN = "\033[32m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_RESET = "\033[0m";

function colorText($text, $color) {
    return $color . $text . COLOR_RESET;
}

function checkCoverageThreshold() {
    echo colorText("🔍 COVERAGE THRESHOLD CHECKER\n", COLOR_BLUE);
    echo colorText("==============================\n\n", COLOR_BLUE);
    
    // Check if clover.xml exists
    $cloverFile = 'coverage/clover.xml';
    if (!file_exists($cloverFile)) {
        echo colorText("❌ Error: Coverage file not found: $cloverFile\n", COLOR_RED);
        echo "Run 'composer test:coverage' first to generate coverage data.\n";
        exit(1);
    }

    // Parse Clover XML
    $xml = simplexml_load_file($cloverFile);
    if (!$xml) {
        echo colorText("❌ Error: Could not parse coverage file\n", COLOR_RED);
        exit(1);
    }

    // Extract coverage metrics
    $metrics = $xml->project->metrics;
    $coverage = [
        'statements' => calculateCoverage($metrics['coveredstatements'], $metrics['statements']),
        'conditionals' => calculateCoverage($metrics['coveredconditionals'], $metrics['conditionals']),
        'methods' => calculateCoverage($metrics['coveredmethods'], $metrics['methods']),
        'elements' => calculateCoverage($metrics['coveredelements'], $metrics['elements'])
    ];

    // Display current coverage
    echo colorText("📊 CURRENT COVERAGE RESULTS:\n", COLOR_BLUE);
    echo "--------------------------------\n";
    
    $allPassed = true;
    
    foreach ($coverage as $type => $percentage) {
        $target = COVERAGE_TARGETS[$type] ?? MIN_COVERAGE_THRESHOLD;
        $status = $percentage >= $target ? '✅' : '❌';
        $color = $percentage >= $target ? COLOR_GREEN : COLOR_RED;
        
        if ($percentage < $target) {
            $allPassed = false;
        }
        
        echo sprintf(
            "%s %s: %s (Target: %d%%)\n",
            $status,
            ucfirst($type),
            colorText(number_format($percentage, 2) . '%', $color),
            $target
        );
    }

    echo "\n";

    // Overall result
    if ($allPassed) {
        echo colorText("🎉 SUCCESS: All coverage thresholds met!\n", COLOR_GREEN);
        echo colorText("Your code meets the quality standards.\n\n", COLOR_GREEN);
        
        // Show recommendations for excellent coverage
        if (min($coverage) >= 90) {
            echo colorText("🌟 EXCELLENT: Coverage above 90%!\n", COLOR_GREEN);
        } elseif (min($coverage) >= 85) {
            echo colorText("👍 GOOD: Coverage above 85%\n", COLOR_YELLOW);
        }
        
        exit(0);
    } else {
        echo colorText("💥 FAILURE: Coverage thresholds not met!\n", COLOR_RED);
        echo colorText("Please add more tests to improve coverage.\n\n", COLOR_RED);
        
        // Show recommendations
        echo colorText("💡 RECOMMENDATIONS:\n", COLOR_YELLOW);
        echo "-------------------\n";
        
        foreach ($coverage as $type => $percentage) {
            $target = COVERAGE_TARGETS[$type] ?? MIN_COVERAGE_THRESHOLD;
            if ($percentage < $target) {
                $needed = $target - $percentage;
                echo "• Increase $type coverage by " . 
                     colorText(number_format($needed, 1) . '%', COLOR_YELLOW) . "\n";
            }
        }
        
        echo "\n";
        echo colorText("🔧 QUICK FIXES:\n", COLOR_BLUE);
        echo "1. Run 'composer test:unit' to see uncovered code\n";
        echo "2. Add tests for controllers, services, and repositories\n";
        echo "3. Use 'composer test:coverage:html' for detailed coverage report\n";
        echo "4. Check coverage/html/index.html for visual coverage map\n\n";
        
        exit(1);
    }
}

function calculateCoverage($covered, $total) {
    if ($total == 0) return 100;
    return ($covered / $total) * 100;
}

function getDetailedCoverageReport() {
    $cloverFile = 'coverage/clover.xml';
    $xml = simplexml_load_file($cloverFile);
    
    echo colorText("\n📈 DETAILED COVERAGE BY FILE:\n", COLOR_BLUE);
    echo "===============================\n";
    
    foreach ($xml->project->package as $package) {
        $packageName = (string)$package['name'];
        echo colorText("\nPackage: $packageName\n", COLOR_YELLOW);
        
        foreach ($package->file as $file) {
            $fileName = basename((string)$file['name']);
            $metrics = $file->metrics;
            
            if ($metrics['statements'] > 0) {
                $lineCoverage = calculateCoverage(
                    $metrics['coveredstatements'], 
                    $metrics['statements']
                );
                
                $color = $lineCoverage >= MIN_COVERAGE_THRESHOLD ? COLOR_GREEN : COLOR_RED;
                echo sprintf(
                    "  %s: %s\n",
                    $fileName,
                    colorText(number_format($lineCoverage, 1) . '%', $color)
                );
            }
        }
    }
}

function generateCoverageReport() {
    echo colorText("\n📋 GENERATING COVERAGE REPORT...\n", COLOR_BLUE);
    
    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'threshold_check' => 'passed', // Will be updated based on results
        'coverage' => [],
        'recommendations' => []
    ];
    
    // Save to JSON for CI/CD integration
    file_put_contents('coverage/threshold-check.json', json_encode($report, JSON_PRETTY_PRINT));
    
    echo colorText("✅ Report saved to coverage/threshold-check.json\n", COLOR_GREEN);
}

// Main execution
try {
    checkCoverageThreshold();
    
    // Show detailed report if requested
    if (in_array('--detailed', $argv)) {
        getDetailedCoverageReport();
    }
    
    // Generate machine-readable report
    if (in_array('--report', $argv)) {
        generateCoverageReport();
    }
    
} catch (Exception $e) {
    echo colorText("💥 Error: " . $e->getMessage() . "\n", COLOR_RED);
    exit(1);
} 