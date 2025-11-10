# Sticky Notes Component

A fully interactive sticky notes board built with Laravel Livewire and Tailwind CSS. Create, organize, edit, and delete virtual sticky notes on a canvas.

## Features

✅ **Create Notes** - Add new sticky notes with a single click
✅ **Color Selection** - Choose from 6 vibrant colors (yellow, pink, blue, green, purple, orange)
✅ **Drag & Drop** - Click and drag notes to reposition them anywhere on the canvas
✅ **Edit Text** - Click on any note to edit its content inline
✅ **Delete Notes** - Remove individual notes or clear all notes at once
✅ **Persistent Storage** - Notes are saved in the session and persist across page refreshes
✅ **Responsive Design** - Works beautifully on desktop and tablet devices
✅ **Dark Mode Support** - Automatically adapts to your system theme

## How to Use

### Accessing the Sticky Notes Board

1. Navigate to `/sticky-notes` in your browser
2. Or click the "Sticky Notes" link in the main navigation

### Creating Notes

1. Select a color from the color palette in the toolbar
2. Click the "Add Note" button
3. A new sticky note will appear on the canvas

### Editing Notes

1. Click on the text area of any note
2. Type your content
3. Press ESC or click outside the note to save
4. Changes are automatically saved every 500ms while typing

### Moving Notes

1. Click and hold the small circular handle at the top-left of any note
2. Drag the note to your desired position
3. Release to drop the note in place
4. Position is automatically saved

### Deleting Notes

**Single Note:**
- Click the X button in the top-right corner of the note
- Confirm the deletion when prompted

**All Notes:**
- Click the "Clear All" button in the toolbar (appears when you have notes)
- Confirm to delete all notes

### Color Options

- **Yellow** - Classic sticky note look
- **Pink** - Soft and friendly
- **Blue** - Cool and calm
- **Green** - Fresh and natural
- **Purple** - Creative and unique
- **Orange** - Warm and energetic

## Technical Details

### File Structure

- **Component:** `resources/views/livewire/sticky-notes.blade.php`
- **Route:** Defined in `routes/web.php`
- **Storage:** Session-based (can be easily changed to database)

### Technologies Used

- **Laravel Livewire** - For reactive components and backend logic
- **Alpine.js** - For local component state (editing mode)
- **Tailwind CSS** - For styling
- **Vanilla JavaScript** - For drag-and-drop functionality
- **Google Fonts** - "Shadows Into Light" for handwritten feel

### Customization

#### Change Colors

Edit the CSS variables in the component:

```css
:root {
    --note-color-yellow: #fef08a;
    --note-color-pink: #fbcfe8;
    /* Add more colors here */
}
```

#### Change Note Size

Modify the width and height in the component:

```html
<div class="w-64 h-64"> <!-- Change these values -->
```

#### Add Database Storage

Replace session storage with database:

1. Create a `notes` migration
2. Create a `Note` model
3. Update the component methods to use Eloquent instead of session

#### Disable Auto-save

Change the debounce delay in the textarea:

```html
wire:model.live.debounce.500ms  <!-- Change to higher value or remove .live -->
```

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Touch devices supported

## Tips & Tricks

1. **Organize by Color** - Use different colors to categorize notes (yellow for todos, blue for ideas, etc.)
2. **Keyboard Shortcuts** - Press ESC to stop editing a note quickly
3. **Quick Copy** - Add a note with content, then create variations by copying the text
4. **Layout Planning** - Use the drag feature to arrange notes in columns or rows for better organization

## Future Enhancements

Potential features to add:

- [ ] Export notes to PDF/image
- [ ] Share notes with other users
- [ ] Add note sizing (small, medium, large)
- [ ] Add note rotation for a more natural look
- [ ] Add rich text formatting
- [ ] Add categories/tags
- [ ] Add search functionality
- [ ] Add undo/redo
- [ ] Add keyboard shortcuts for creating/deleting notes
- [ ] Add collaboration (real-time multi-user editing)

## Troubleshooting

**Notes not saving:**
- Ensure your session configuration is correct in `config/session.php`
- Check that session storage is writable

**Drag and drop not working:**
- Make sure JavaScript is enabled
- Check browser console for errors
- Try refreshing the page

**Styling issues:**
- Run `npm run dev` or `npm run build` to compile assets
- Clear your browser cache

## License

This component is part of your Laravel application and follows the same license.

