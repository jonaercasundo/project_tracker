<?php

use App\Http\Controllers\TravelLiquidationController;
use App\Models\BudgetRequest;
use App\Models\User;
use App\Services\MiFinancialWorkflowService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\MIWorkflowConsoleKernel;

require dirname(__DIR__).'/vendor/autoload.php';
require __DIR__.'/MIWorkflowTestCase.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->singleton(Kernel::class, MIWorkflowConsoleKernel::class);
$app->make(Kernel::class)->bootstrap();
$token = getenv('MI_TEST_MYSQL_TOKEN');
$mysql = config('database.connections.mysql');
if (! is_string($token) || ! preg_match('/^[a-f0-9]{24}$/D', $token) || ! in_array($mysql['host'], ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('Isolated local test database required.');
}
$target = 'mi_workflow_test_'.$token;
if ($target === $mysql['database']) {
    throw new RuntimeException('Application database prohibited.');
}
config(['database.default' => 'mi_test_mysql', 'database.connections.mi_test_mysql' => array_replace($mysql, ['database' => $target, 'url' => null, 'strict' => true]),
    'cache.default' => 'array', 'session.driver' => 'array']);
$user = User::findOrFail($argv[2]);
session(['company_id' => (int) $argv[3]]);
auth()->login($user);
$start = (float) $argv[5];
while (microtime(true) < $start) {
    usleep(10000);
}
try {
    $service = app(MiFinancialWorkflowService::class);
    $budget = BudgetRequest::findOrFail($argv[4]);
    match ($argv[1]) {
        'approve' => $service->approveBudget($budget, $user),
        'release' => $service->releaseBudget($budget, $user),
        'receive' => $service->confirmReceipt($budget, $user),
        'travel' => (function () use ($budget, $user): void {
            $request = Request::create('/travel_liquidation', 'POST', [
                'budget_request_id' => $budget->getKey(), 'items' => [[
                    'expense_category' => 'Travel', 'particular' => 'Race expense',
                    'actual_cash' => '0.10', 'actual_credit_card' => '0.20', 'actual_travel_agent' => '0.00', 'receipt_attached' => 'yes',
                ]],
            ]);
            $request->setUserResolver(fn () => $user);
            app(TravelLiquidationController::class)->store($request);
        })(),
        default => throw new RuntimeException('Unknown test action.'),
    };
    echo '200';
} catch (HttpException $exception) {
    echo $exception->getStatusCode();
}
