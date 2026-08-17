const test = require('node:test');
const assert = require('node:assert/strict');
const cropper = require('../../../src/views/form/scripts/cropper.js');

class FakeFile {
    constructor(parts, name, options) {
        this.parts = parts;
        this.name = name;
        this.type = options.type;
        this.lastModified = options.lastModified;
    }
}

class FakeDataTransfer {
    constructor() {
        this.files = [];
        this.items = {
            add: (file) => this.files.push(file),
        };
    }
}

test('canvasOptions exports the exact configured dimensions', () => {
    assert.deepEqual(cropper.canvasOptions({width: 640, height: 480}), {
        width: 640,
        height: 480,
    });
});

test('replaceInputFile submits the cropped file', () => {
    const input = {files: null};
    const original = {name: 'student.jpg', type: 'image/jpeg'};
    const blob = {bytes: 'cropped'};

    const croppedFile = cropper.replaceInputFile(
        input,
        original,
        blob,
        FakeDataTransfer,
        FakeFile,
        12345,
    );

    assert.equal(input.files.length, 1);
    assert.equal(input.files[0], croppedFile);
    assert.equal(croppedFile.name, 'student.jpg');
    assert.equal(croppedFile.type, 'image/jpeg');
    assert.equal(croppedFile.lastModified, 12345);
    assert.equal(croppedFile.parts[0], blob);
});

test('clearSelection discards the newly selected file', () => {
    const input = {value: 'new-image'};

    cropper.clearSelection(input);

    assert.equal(input.value, '');
});
