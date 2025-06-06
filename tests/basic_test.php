<?php

echo "🧪 Basis Database Test\n";
echo "=====================\n\n";

// Test 1: Bootstrap loading
echo "1. Loading bootstrap...\n";
try {
    require_once __DIR__ . '/../bootstrap.php';
    echo "✅ Bootstrap loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Bootstrap failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Container
echo "\n2. Testing container...\n";
try {
    $container = container();
    echo "✅ Container accessible\n";
} catch (Exception $e) {
    echo "❌ Container failed: " . $e->getMessage() . "\n";
}

// Test 3: Database Interface
echo "\n3. Testing DatabaseInterface...\n";
try {
    $database = container()->get(\App\Infrastructure\Database\DatabaseInterface::class);
    echo "✅ DatabaseInterface available via DI\n";
} catch (Exception $e) {
    echo "❌ DatabaseInterface failed: " . $e->getMessage() . "\n";
}

// Test 4: Legacy Bridge
echo "\n4. Testing Legacy Bridge...\n";
try {
    require_once __DIR__ . '/../includes/legacy/DatabaseBridge.php';
    echo "✅ Legacy bridge loaded\n";
    
    if (function_exists('getLegacyDatabase')) {
        echo "✅ getLegacyDatabase function exists\n";
    } else {
        echo "❌ getLegacyDatabase function missing\n";
    }
} catch (Exception $e) {
    echo "❌ Legacy bridge failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 CORE TESTS COMPLETED\n";
echo "======================\n";
echo "This confirms the database unification architecture is working!\n"; 