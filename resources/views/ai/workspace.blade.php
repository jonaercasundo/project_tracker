<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-900">AI Assistant Workspace</h1>
    </x-slot>

    <div class="min-h-[calc(100vh-10rem)] bg-slate-100 p-4 sm:p-6">
        <x-ai-assistant workspace />
    </div>
</x-app-layout>
