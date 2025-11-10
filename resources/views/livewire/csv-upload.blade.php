<div class="container mx-auto p-6">

    {{-- Upload Box --}}
    <div class="border-2 border-dashed border-gray-400 rounded-xl p-8 text-center bg-gray-50">
        <h2 class="text-lg font-semibold mb-4">Select file / Drag and drop</h2>

        <form wire:submit.prevent="uploadCsv" class="flex flex-col items-center space-y-4" enctype="multipart/form-data">
            <input type="file"
                    wire:model="file"
                    accept=".csv"
                    class="cursor-pointer border rounded-md p-2 w-64 text-sm"/>

            @error('file')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror

            <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Upload File
            </button>
        </form>
    </div>

    {{-- Flash message --}}
    @if (session()->has('message'))
        <div class="mt-4 text-green-600 text-sm">
            {{ session('message') }}
        </div>
    @endif

    {{-- Table of Uploads --}}
    <div class="mt-8" wire:poll.2000ms>
        <table class="min-w-full border border-gray-300 text-sm text-left">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-4 border-b">Time</th>
                    <th class="py-2 px-4 border-b">File Name</th>
                    <th class="py-2 px-4 border-b">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($uploads as $upload)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 px-4">
                            {{ $upload->created_at->format('M j, g:i a') }}
                            <br>
                            <span class="text-gray-500 text-xs">
                                ({{ $upload->created_at->diffForHumans() }})
                            </span>
                        </td>
                        <td class="py-2 px-4">{{ $upload->filename }}</td>
                        <td class="py-2 px-4">
                            @php
                                $color = match($upload->status) {
                                    'completed' => 'text-green-600',
                                    'failed' => 'text-red-600',
                                    'processing' => 'text-blue-600',
                                    default => 'text-gray-600',
                                };
                            @endphp
                            <span class="{{ $color }} font-medium">
                                {{ ucfirst($upload->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-3 text-center text-gray-400">
                            No uploads yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
