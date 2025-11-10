<?php

use Livewire\Volt\Component;
use App\Models\StickyNote;
use App\Models\StickyNoteBoard;

new class extends Component {
    public $notes = [];
    public $canvasColor = 'white';
    public $boardTitle = 'Sticky Notes Board';
    public $boardDescription = 'Click anywhere on the canvas to add a note';

    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Load board settings
        $board = StickyNoteBoard::firstOrCreate(
            ['user_id' => auth()->id()],
            [
                'board_title' => 'Sticky Notes Board',
                'board_description' => 'Click anywhere on the canvas to add a note',
                'canvas_color' => 'white',
            ]
        );

        $this->boardTitle = $board->board_title;
        $this->boardDescription = $board->board_description;
        $this->canvasColor = $board->canvas_color;

        // Load notes from database
        $dbNotes = StickyNote::where('user_id', auth()->id())->get();
        $this->notes = $dbNotes->map(function ($note) {
            return [
                'id' => $note->id,
                'title' => $note->title ?? '',
                'text' => $note->text ?? '',
                'color' => $note->color,
                'icon' => $note->icon,
                'x' => $note->x,
                'y' => $note->y,
            ];
        })->toArray();
    }

    public function addNote($x, $y, $color)
    {
        $stickyNote = StickyNote::create([
            'user_id' => auth()->id(),
            'title' => '',
            'text' => '',
            'color' => $color,
            'icon' => 'cupcake',
            'x' => $x,
            'y' => $y,
        ]);

        $this->notes[] = [
            'id' => $stickyNote->id,
            'title' => '',
            'text' => '',
            'color' => $color,
            'icon' => 'cupcake',
            'x' => $x,
            'y' => $y,
        ];
    }

    public function updateNote($id, $text)
    {
        StickyNote::where('id', $id)->where('user_id', auth()->id())->update(['text' => $text]);
        
        foreach ($this->notes as &$note) {
            if ($note['id'] == $id) {
                $note['text'] = $text;
                break;
            }
        }
    }

    public function updateTitle($id, $title)
    {
        StickyNote::where('id', $id)->where('user_id', auth()->id())->update(['title' => $title]);
        
        foreach ($this->notes as &$note) {
            if ($note['id'] == $id) {
                $note['title'] = $title;
                break;
            }
        }
    }

    public function updateIcon($id, $icon)
    {
        StickyNote::where('id', $id)->where('user_id', auth()->id())->update(['icon' => $icon]);
        
        foreach ($this->notes as &$note) {
            if ($note['id'] == $id) {
                $note['icon'] = $icon;
                break;
            }
        }
    }

    public function updatePosition($id, $x, $y)
    {
        StickyNote::where('id', $id)->where('user_id', auth()->id())->update(['x' => $x, 'y' => $y]);
        
        foreach ($this->notes as &$note) {
            if ($note['id'] == $id) {
                $note['x'] = $x;
                $note['y'] = $y;
                break;
            }
        }
    }

    public function deleteNote($id)
    {
        StickyNote::where('id', $id)->where('user_id', auth()->id())->delete();
        
        $this->notes = array_values(array_filter($this->notes, function ($note) use ($id) {
            return $note['id'] != $id;
        }));
    }

    public function updateCanvasColor($color)
    {
        $this->canvasColor = $color;
        
        StickyNoteBoard::updateOrCreate(
            ['user_id' => auth()->id()],
            ['canvas_color' => $color]
        );
    }

    public function updateBoardTitle($title)
    {
        $this->boardTitle = $title;
        
        StickyNoteBoard::updateOrCreate(
            ['user_id' => auth()->id()],
            ['board_title' => $title]
        );
    }

    public function updateBoardDescription($description)
    {
        $this->boardDescription = $description;
        
        StickyNoteBoard::updateOrCreate(
            ['user_id' => auth()->id()],
            ['board_description' => $description]
        );
    }

    public function clearAll()
    {
        StickyNote::where('user_id', auth()->id())->delete();
        $this->notes = [];
    }
}; ?>

<div>
<style>
    :root {
        --note-color-yellow: #fef08a;
        --note-color-pink: #fbcfe8;
        --note-color-blue: #bfdbfe;
        --note-color-green: #bbf7d0;
        --note-color-purple: #e9d5ff;
        --note-color-orange: #fed7aa;
    }

    .dark {
        --note-color-yellow: #facc15;
        --note-color-pink: #f472b6;
        --note-color-blue: #60a5fa;
        --note-color-green: #4ade80;
        --note-color-purple: #c084fc;
        --note-color-orange: #fb923c;
    }

    .font-handwriting {
        font-family: 'Shadows Into Light', 'Comic Sans MS', cursive;
    }

    .sticky-note {
        user-select: none;
        touch-action: none;
        cursor: default;
    }

    .sticky-note.dragging {
        z-index: 1000;
        cursor: grabbing !important;
    }

    .sticky-note.dragging > div {
        transform: rotate(5deg) scale(1.05);
        transition: transform 0.1s ease;
    }

    .note-header {
        cursor: grab;
    }

    .note-header:active {
        cursor: grabbing;
    }
</style>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800" x-data="{ 
    showDeleteModal: false, 
    deleteNoteId: null, 
    deleteType: 'single',
    editingTitle: false,
    editingDescription: false
}">
    <div class="container mx-auto px-4 py-6">
        {{-- Toolbar --}}
        <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm border-b border-slate-200 dark:border-slate-700 shadow-sm rounded-t-xl px-4 py-4">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex-1 max-w-xl">
                    {{-- Editable Board Title --}}
                    <div @click="editingTitle = true" class="cursor-pointer group">
                        <input
                            x-show="editingTitle"
                            x-ref="titleInput"
                            type="text"
                            wire:model.live.debounce.500ms="boardTitle"
                            wire:change="updateBoardTitle($event.target.value)"
                            @blur="editingTitle = false"
                            @keydown.enter="editingTitle = false; $event.target.blur()"
                            @keydown.escape="editingTitle = false"
                            class="text-2xl font-bold text-slate-800 dark:text-white bg-transparent border-2 border-blue-500 rounded px-2 py-1 outline-none w-full"
                            placeholder="Board Title..."
                            maxlength="50"
                            x-init="$watch('editingTitle', value => value && setTimeout(() => $refs.titleInput?.focus(), 10))"
                        />
                        <h1 
                            x-show="!editingTitle"
                            class="text-2xl font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors"
                        >
                            {{ $boardTitle }}
                            <svg class="inline-block w-4 h-4 ml-1 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </h1>
                    </div>

                    {{-- Editable Board Description --}}
                    <div @click="editingDescription = true" class="cursor-pointer group mt-1">
                        <input
                            x-show="editingDescription"
                            x-ref="descriptionInput"
                            type="text"
                            wire:model.live.debounce.500ms="boardDescription"
                            wire:change="updateBoardDescription($event.target.value)"
                            @blur="editingDescription = false"
                            @keydown.enter="editingDescription = false; $event.target.blur()"
                            @keydown.escape="editingDescription = false"
                            class="text-sm text-slate-600 dark:text-slate-400 bg-transparent border-2 border-blue-500 rounded px-2 py-1 outline-none w-full"
                            placeholder="Board description..."
                            maxlength="100"
                            x-init="$watch('editingDescription', value => value && setTimeout(() => $refs.descriptionInput?.focus(), 10))"
                        />
                        <p 
                            x-show="!editingDescription"
                            class="text-sm text-slate-600 dark:text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors"
                        >
                            {{ $boardDescription }}
                            <svg class="inline-block w-3 h-3 ml-1 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4 flex-wrap">
                    {{-- Canvas Color Picker --}}
                    <div class="flex items-center gap-2 px-3 py-2 bg-slate-100 dark:bg-slate-700 rounded-lg border border-slate-200 dark:border-slate-600">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300 whitespace-nowrap">Canvas:</span>
                        <div class="flex gap-1.5">
                            @foreach(['white', 'slate', 'blue', 'purple', 'pink', 'green'] as $color)
                                @php
                                    $colorClasses = [
                                        'white' => 'bg-white border-slate-300',
                                        'slate' => 'bg-slate-100 border-slate-300',
                                        'blue' => 'bg-blue-50 border-blue-200',
                                        'purple' => 'bg-purple-50 border-purple-200',
                                        'pink' => 'bg-pink-50 border-pink-200',
                                        'green' => 'bg-green-50 border-green-200',
                                    ];
                                @endphp
                                <button
                                    wire:click="updateCanvasColor('{{ $color }}')"
                                    class="w-7 h-7 rounded-md border-2 transition-all hover:scale-110 {{ $colorClasses[$color] }} {{ $canvasColor === $color ? 'ring-2 ring-blue-500 ring-offset-2' : '' }}"
                                    title="{{ ucfirst($color) }} canvas"
                                ></button>
                            @endforeach
                        </div>
                    </div>

                    @if(count($notes) > 0)
                        <button
                            @click="deleteType = 'all'; showDeleteModal = true"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors shadow-sm"
                        >
                            Clear All Notes
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Canvas --}}
        <div>
        @php
            $canvasStyles = [
                'white' => 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700',
                'slate' => 'bg-slate-50 dark:bg-slate-700 border-slate-300 dark:border-slate-600',
                'blue' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
                'purple' => 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800',
                'pink' => 'bg-pink-50 dark:bg-pink-900/20 border-pink-200 dark:border-pink-800',
                'green' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
            ];
            $canvasColorClass = $canvasStyles[$canvasColor] ?? $canvasStyles['white'];
        @endphp
        <div
            x-data="{ 
                showColorPicker: false, 
                pickerX: 0, 
                pickerY: 0,
                canvasOffsetX: 0,
                canvasOffsetY: 0,
                pickerPosition: 'right'
            }"
            id="canvas"
            class="relative w-full min-h-[calc(100vh-16rem)] {{ $canvasColorClass }} rounded-b-xl shadow-lg border border-t-0 cursor-crosshair transition-colors duration-300"
            style="background-image: radial-gradient(circle, {{ $canvasColor === 'white' ? '#e5e7eb' : 'rgba(0,0,0,0.05)' }} 1px, transparent 1px); background-size: 20px 20px;"
            @click="
                if (($event.target.id === 'canvas' || $event.target.closest('.empty-state')) && 
                    !$event.target.closest('.sticky-note') && 
                    !$event.target.closest('[x-show]')) {
                    const rect = $event.currentTarget.getBoundingClientRect();
                    const clickX = $event.clientX - rect.left;
                    const clickY = $event.clientY - rect.top;
                    
                    canvasOffsetX = clickX - 128;
                    canvasOffsetY = clickY - 128;
                    pickerX = clickX;
                    pickerY = clickY;
                    
                    // Determine if there's enough space on the right for the picker
                    const spaceOnRight = rect.width - clickX;
                    pickerPosition = spaceOnRight < 250 ? 'left' : 'right';
                    
                    showColorPicker = true;
                }
            "
        >
            @if(count($notes) === 0)
                <div class="empty-state absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="text-center text-slate-400 dark:text-slate-500">
                        <svg class="w-24 h-24 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-xl font-medium">No notes yet</p>
                        <p class="text-sm mt-2">Click anywhere on the canvas to add a sticky note</p>
                    </div>
                </div>
            @endif

            {{-- Color Picker Popup --}}
            <div
                x-show="showColorPicker"
                x-transition
                @click.away="showColorPicker = false"
                :style="`top: ${pickerY}px;`"
                :class="pickerPosition === 'right' ? 'left-[' + (pickerX + 20) + 'px]' : 'right-[20px]'"
                class="absolute z-[1001]"
                x-bind:style="`top: ${pickerY}px; ${pickerPosition === 'right' ? 'left: ' + (pickerX + 20) + 'px;' : 'right: 20px;'}`"
            >
                <div class="bg-white dark:bg-slate-700 rounded-xl shadow-2xl border-2 border-slate-300 dark:border-slate-600 p-4">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3 text-center whitespace-nowrap">Choose a color</p>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach(['yellow', 'pink', 'blue', 'green', 'purple', 'orange'] as $color)
                            <button
                                @click="
                                    $wire.addNote(canvasOffsetX, canvasOffsetY, '{{ $color }}');
                                    showColorPicker = false;
                                "
                                class="w-14 h-14 rounded-lg border-2 border-slate-300 dark:border-slate-600 transition-all hover:scale-110 hover:border-slate-800 dark:hover:border-white hover:shadow-lg"
                                style="background-color: var(--note-color-{{ $color }})"
                                title="{{ ucfirst($color) }}"
                            ></button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Sticky Notes --}}
            @foreach($notes as $note)
                <div
                    data-note-id="{{ $note['id'] }}"
                    class="sticky-note absolute cursor-move"
                    style="left: {{ $note['x'] }}px; top: {{ $note['y'] }}px; --note-color: var(--note-color-{{ $note['color'] }});"
                    x-data="{ editingContent: false, editingTitle: false, showIconPicker: false }"
                >
                    <div class="w-64 h-64 rounded-lg shadow-lg hover:shadow-xl transition-shadow note-{{ $note['color'] }}"
                         style="background-color: var(--note-color);">
                        
                        {{-- Note Header (Draggable) --}}
                        <div class="note-header flex flex-col gap-2 p-3 pb-2 border-b border-black/10 cursor-grab hover:bg-black/5 transition-colors">
                            {{-- Top row: Icon selector and Delete button --}}
                            <div class="flex items-center justify-between">
                                <div class="relative icon-picker-container">
                                    {{-- Icon Button --}}
                                    <button
                                        @click.stop="showIconPicker = !showIconPicker"
                                        class="p-1 rounded hover:bg-black/10 transition-colors pointer-events-auto"
                                        title="Change icon"
                                    >
                                        @php
                                            $icons = [
                                                'cupcake' => '🧁',
                                                'sparkles' => '✨',
                                                'star' => '⭐',
                                                'heart' => '❤️',
                                                'flag' => '🚩',
                                                'bookmark' => '🔖',
                                                'lightbulb' => '💡',
                                                'fire' => '🔥',
                                                'bell' => '🔔',
                                                'check' => '✅',
                                                'pin' => '📌',
                                            ];
                                            $currentIcon = $icons[$note['icon']] ?? $icons['cupcake'];
                                        @endphp
                                        <span class="text-xl">{{ $currentIcon }}</span>
                                    </button>

                                    {{-- Icon Picker Dropdown --}}
                                    <div
                                        x-show="showIconPicker"
                                        x-transition
                                        @click.away="showIconPicker = false"
                                        class="absolute top-8 left-0 z-[1002] bg-white dark:bg-slate-700 rounded-lg shadow-2xl border-2 border-slate-300 dark:border-slate-600 p-3 w-48"
                                        style="min-width: 200px;"
                                    >
                                        <p class="text-xs font-medium text-slate-700 dark:text-slate-300 mb-2 whitespace-nowrap">Choose icon</p>
                                        <div class="grid grid-cols-5 gap-2 w-full">
                                            @foreach(['cupcake', 'sparkles', 'star', 'heart', 'flag', 'bookmark', 'lightbulb', 'fire', 'bell', 'check', 'pin'] as $iconName)
                                                <button
                                                    type="button"
                                                    @click.stop="
                                                        $wire.updateIcon({{ $note['id'] }}, '{{ $iconName }}');
                                                        showIconPicker = false;
                                                    "
                                                    class="flex items-center justify-center p-2 rounded hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors text-2xl {{ $note['icon'] === $iconName ? 'bg-slate-200 dark:bg-slate-600 ring-2 ring-blue-500' : '' }}"
                                                    title="{{ ucfirst($iconName) }}"
                                                >
                                                    {{ $icons[$iconName] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <button
                                    @click.stop="deleteNoteId = {{ $note['id'] }}; deleteType = 'single'; showDeleteModal = true"
                                    class="text-red-600 hover:text-red-800 transition-colors z-10 pointer-events-auto"
                                    title="Delete note"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            {{-- Title area --}}
                            <div 
                                class="pointer-events-auto cursor-text"
                                @click.stop="editingTitle = true"
                            >
                                <input
                                    x-show="editingTitle"
                                    x-ref="titleInput"
                                    type="text"
                                    wire:model.live.debounce.500ms="notes.{{ array_search($note, $notes) }}.title"
                                    wire:change="updateTitle({{ $note['id'] }}, $event.target.value)"
                                    @blur="editingTitle = false"
                                    @keydown.enter="editingTitle = false; $event.target.blur()"
                                    @keydown.escape="editingTitle = false"
                                    class="w-full bg-transparent border-none outline-none text-slate-800 font-semibold text-sm placeholder-slate-500"
                                    placeholder="Add title..."
                                    maxlength="30"
                                    x-init="$watch('editingTitle', value => value && setTimeout(() => $refs.titleInput?.focus(), 10))"
                                />
                                <div
                                    x-show="!editingTitle"
                                    class="text-slate-800 font-semibold text-sm min-h-[1.25rem]"
                                >
                                    @if(empty($note['title']))
                                        <span class="text-slate-500 italic text-xs">Click to add title...</span>
                                    @else
                                        {{ $note['title'] }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Note Content --}}
                        <div class="p-3 h-[calc(100%-4.5rem)] overflow-hidden" @click="editingContent = true">
                            <textarea
                                x-show="editingContent"
                                x-ref="textarea"
                                wire:model.live.debounce.500ms="notes.{{ array_search($note, $notes) }}.text"
                                wire:change="updateNote({{ $note['id'] }}, $event.target.value)"
                                @blur="editingContent = false"
                                @keydown.escape="editingContent = false"
                                class="w-full h-full bg-transparent border-none outline-none resize-none text-slate-800 font-handwriting text-lg leading-relaxed"
                                placeholder="Click to write..."
                                x-init="$watch('editingContent', value => value && setTimeout(() => $refs.textarea?.focus(), 10))"
                            >{{ $note['text'] }}</textarea>

                            <div
                                x-show="!editingContent"
                                class="w-full h-full text-slate-800 font-handwriting text-lg leading-relaxed whitespace-pre-wrap break-words cursor-text"
                            >
                                @if(empty($note['text']))
                                    <span class="text-slate-500 italic">Click to write...</span>
                                @else
                                    {{ $note['text'] }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    </div>

    {{-- Custom Delete Confirmation Modal --}}
    <div
        x-show="showDeleteModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @click="showDeleteModal = false"
        style="display: none;"
    >
        <div
            @click.stop
            x-show="showDeleteModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-700"
        >
            {{-- Modal Icon --}}
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-red-100 dark:bg-red-900/30 rounded-full">
                <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            {{-- Modal Title --}}
            <h3 class="text-xl font-bold text-center text-slate-900 dark:text-white mb-2">
                <span x-show="deleteType === 'single'">Delete Note?</span>
                <span x-show="deleteType === 'all'">Delete All Notes?</span>
            </h3>

            {{-- Modal Message --}}
            <p class="text-center text-slate-600 dark:text-slate-400 mb-6">
                <span x-show="deleteType === 'single'">
                    Are you sure you want to delete this sticky note? This action cannot be undone.
                </span>
                <span x-show="deleteType === 'all'">
                    Are you sure you want to delete all {{ count($notes) }} sticky notes? This action cannot be undone.
                </span>
            </p>

            {{-- Modal Actions --}}
            <div class="flex gap-3">
                <button
                    @click="showDeleteModal = false"
                    class="flex-1 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg font-medium transition-colors"
                >
                    Cancel
                </button>
                <button
                    @click="
                        if (deleteType === 'single') {
                            $wire.deleteNote(deleteNoteId);
                        } else {
                            $wire.clearAll();
                        }
                        showDeleteModal = false;
                    "
                    class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors shadow-lg shadow-red-600/30"
                >
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Add Google Font for handwriting effect
    if (!document.querySelector('link[href*="Shadows+Into+Light"]')) {
        const link = document.createElement('link');
        link.href = 'https://fonts.googleapis.com/css2?family=Shadows+Into+Light&display=swap';
        link.rel = 'stylesheet';
        document.head.appendChild(link);
    }

    document.addEventListener('DOMContentLoaded', function() {
        initializeDragAndDrop();
    });

    // Reinitialize after Livewire updates
    document.addEventListener('livewire:navigated', function() {
        initializeDragAndDrop();
    });

    Livewire.hook('morph.updated', () => {
        initializeDragAndDrop();
    });

    function initializeDragAndDrop() {
        const canvas = document.getElementById('canvas');
        if (!canvas) return;

        const notes = canvas.querySelectorAll('.sticky-note');
        
        notes.forEach(note => {
            // Remove existing listeners to prevent duplicates
            note.isDraggable = note.isDraggable || false;
            if (note.isDraggable) return;
            note.isDraggable = true;

            let isDragging = false;
            let currentX;
            let currentY;
            let initialX;
            let initialY;
            let xOffset = parseInt(note.style.left) || 0;
            let yOffset = parseInt(note.style.top) || 0;

            const header = note.querySelector('.note-header');
            if (!header) return;

            header.addEventListener('mousedown', dragStart);
            header.addEventListener('touchstart', dragStart);

            function dragStart(e) {
                // Don't drag if clicking on buttons, title input, title area, or icon picker
                if (e.target.closest('button') || 
                    e.target.tagName === 'INPUT' || 
                    e.target.closest('.pointer-events-auto.cursor-text') ||
                    e.target.closest('.icon-picker-container')) {
                    return;
                }

                if (e.type === 'touchstart') {
                    initialX = e.touches[0].clientX - xOffset;
                    initialY = e.touches[0].clientY - yOffset;
                } else {
                    initialX = e.clientX - xOffset;
                    initialY = e.clientY - yOffset;
                }

                isDragging = true;
                note.classList.add('dragging');

                document.addEventListener('mousemove', drag);
                document.addEventListener('mouseup', dragEnd);
                document.addEventListener('touchmove', drag);
                document.addEventListener('touchend', dragEnd);
            }

            function drag(e) {
                if (!isDragging) return;

                e.preventDefault();

                if (e.type === 'touchmove') {
                    currentX = e.touches[0].clientX - initialX;
                    currentY = e.touches[0].clientY - initialY;
                } else {
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;
                }

                xOffset = currentX;
                yOffset = currentY;

                // Constrain to canvas bounds
                const canvasRect = canvas.getBoundingClientRect();
                const noteWidth = 256; // 16rem (w-64)
                const noteHeight = 256; // 16rem (h-64)
                
                const maxX = canvas.offsetWidth - noteWidth;
                const maxY = canvas.offsetHeight - noteHeight;
                
                xOffset = Math.max(0, Math.min(xOffset, maxX));
                yOffset = Math.max(0, Math.min(yOffset, maxY));

                setTranslate(xOffset, yOffset, note);
            }

            function dragEnd(e) {
                if (!isDragging) return;

                isDragging = false;
                note.classList.remove('dragging');

                document.removeEventListener('mousemove', drag);
                document.removeEventListener('mouseup', dragEnd);
                document.removeEventListener('touchmove', drag);
                document.removeEventListener('touchend', dragEnd);

                // Save position to server
                const noteId = note.getAttribute('data-note-id');
                if (noteId) {
                    Livewire.find(note.closest('[wire\\:id]').getAttribute('wire:id'))
                        .call('updatePosition', parseInt(noteId), Math.round(xOffset), Math.round(yOffset));
                }
            }

            function setTranslate(xPos, yPos, el) {
                el.style.left = xPos + 'px';
                el.style.top = yPos + 'px';
            }
        });
    }
</script>
</div>

