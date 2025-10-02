<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Traits\HasFluxToasts;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class MobileCameraUpload extends Component
{
    use WithFileUploads, HasFluxToasts;

    public $photo;

    public $photos = [];

    public $ticketId;

    public $maxFileSize = 10240;

    public $compressionQuality = 80;

    public $maxWidth = 1920;

    public $maxHeight = 1920;

    protected $rules = [
        'photo' => 'required|image|max:10240',
    ];

    public function mount($ticketId = null)
    {
        $this->ticketId = $ticketId;
    }

    public function updatedPhoto()
    {
        $this->validate();

        try {
            $compressedImage = $this->compressImage($this->photo);

            $filename = uniqid('ticket_photo_') . '.jpg';
            $path = 'ticket-attachments/' . now()->format('Y/m');

            Storage::disk('public')->put($path . '/' . $filename, $compressedImage);

            $this->photos[] = [
                'filename' => $filename,
                'path' => $path . '/' . $filename,
                'url' => Storage::disk('public')->url($path . '/' . $filename),
                'size' => strlen($compressedImage),
                'uploaded_at' => now()->toIso8601String(),
            ];

            $this->dispatch('photo-uploaded', photo: end($this->photos));
            $this->dispatch('success', message: 'Photo uploaded and compressed successfully');

            $this->reset('photo');
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to upload photo: ' . $e->getMessage());
        }
    }

    protected function compressImage($uploadedFile)
    {
        $image = Image::make($uploadedFile->getRealPath());

        if ($image->width() > $this->maxWidth || $image->height() > $this->maxHeight) {
            $image->resize($this->maxWidth, $this->maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $image->orientate();

        return $image->encode('jpg', $this->compressionQuality)->encoded;
    }

    public function removePhoto($index)
    {
        if (isset($this->photos[$index])) {
            $photo = $this->photos[$index];

            Storage::disk('public')->delete($photo['path']);

            unset($this->photos[$index]);
            $this->photos = array_values($this->photos);

            $this->dispatch('photo-removed', index: $index);
            $this->dispatch('success', message: 'Photo removed');
        }
    }

    public function getPhotos()
    {
        return $this->photos;
    }

    public function render()
    {
        return view('livewire.mobile-camera-upload');
    }
}
