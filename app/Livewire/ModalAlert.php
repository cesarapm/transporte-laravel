<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ModalAlert extends Component
{
    public $showModal = false;
    public $message = '';

    protected $listeners = ['showAlert'];

    public function showAlert($message)
    {
        $this->message = $message;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.modal-alert');
    }
}
