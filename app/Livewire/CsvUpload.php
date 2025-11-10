<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Upload;
use Illuminate\Support\Str;
use App\Jobs\ProcessCsvUpload;

class CsvUpload extends Component
{
    use WithFileUploads;

    public $file;

    protected $rules = [
        'file' => 'required|file|mimes:csv,txt|max:51200', // adjust size limit
    ];

    public function uploadCsv()
    {
        $this->validate();
        $originalName = $this->file->getClientOriginalName();
        $filename = time() . '_' . Str::random(6) . '_' . $originalName;
        $path = $this->file->storeAs('uploads', $filename);

        $upload = Upload::create([
            'filename' => $originalName,
            'path' => $path,
            'status' => 'pending',
            'total_rows' => null,
            'processed_rows' => 0,
        ]);

        // Dispatch job to process in background
        ProcessCsvUpload::dispatch($upload->id);

        // reset file input
        $this->file = null;

        session()->flash('message', 'File uploaded and queued for processing.');
    }

    public function render()
    {
        $uploads = Upload::latest()->get();
        return view('livewire.csv-upload', compact('uploads'));
    }
}
