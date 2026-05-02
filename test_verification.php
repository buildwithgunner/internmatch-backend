<?php

use App\Http\Controllers\Api\Auth\PasswordResetController;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

$controller = new PasswordResetController();

echo "Testing Password Reset Validation...\n\n";

// Test Case 1: Non-existent email
echo "Test Case 1: Non-existent email (nonexistent@example.com, student)\n";
try {
    $request = new Request([
        'email' => 'nonexistent@example.com',
        'role' => 'student'
    ]);
    $controller->forgotPassword($request);
    echo "[FAIL] Expected ValidationException was not thrown.\n";
} catch (ValidationException $e) {
    echo "[PASS] Caught expected ValidationException: " . $e->getMessage() . "\n";
    print_r($e->errors());
} catch (\Exception $e) {
    echo "[FAIL] Caught unexpected exception: " . $e->getMessage() . "\n";
}

echo "\n-----------------------------------\n\n";

// Test Case 2: Existent email
echo "Test Case 2: Existent email (student@internmatch.com, student)\n";
try {
    // Ensure the student exists first (in case the seeder hasn't run or something)
    $student = \App\Models\User::where('email', 'student@internmatch.com')->where('role', 'student')->first();
    if (!$student) {
        echo "Creating test student...\n";
        \App\Models\User::factory()->create([
            'name' => 'Test Student',
            'email' => 'student@internmatch.com',
            'role' => 'student'
        ]);
    }

    $request = new Request([
        'email' => 'student@internmatch.com',
        'role' => 'student'
    ]);
    $response = $controller->forgotPassword($request);
    $data = $response->getData();
    
    echo "[PASS] Success response received: " . $data->message . "\n";
} catch (ValidationException $e) {
    echo "[FAIL] Unexpected ValidationException: " . $e->getMessage() . "\n";
    print_r($e->errors());
} catch (\Exception $e) {
    echo "[FAIL] Caught unexpected exception: " . get_class($e) . " - " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
