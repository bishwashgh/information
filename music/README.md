# Music Hub Folder

This folder contains all the music files for your Music Hub section.

## How to Add Music

1. Place your music files (MP3 format) in this folder
2. Open `script.js` and update the `musicFiles` array with your track information:

```javascript
const musicFiles = [
    { title: 'Your Track Title', artist: 'Artist Name', file: 'yourfile.mp3' },
    { title: 'Another Track', artist: 'Artist Name', file: 'anotherfile.mp3' },
    // Add more tracks as needed
];
```

## Supported Audio Formats

- MP3 (recommended)
- WAV
- OGG
- M4A

## Example

If you have a file named `mysong.mp3`, add it like this:

```javascript
{ title: 'My Amazing Song', artist: 'Your Name', file: 'mysong.mp3' }
```

## Tips

- Keep file names simple (no spaces, use hyphens or underscores)
- Optimize your audio files for web (128-192 kbps MP3 is usually sufficient)
- The Music Hub section will automatically display all tracks in the array
