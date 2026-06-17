<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finance App</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">

    <div class="min-h-screen flex">

        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r">
            <div class="p-4 font-bold text-xl">
                Finance Module
            </div>

            <nav class="p-4 space-y-2">
                <a href="/finance/dashboard" class="block">Dashboard</a>
                <a href="/finance/ppl-forms" class="block">PPL Forms</a>
                <a href="/finance/reports" class="block">Reports</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            {{ $slot }}
        </main>

    </div>

</body>
</html>