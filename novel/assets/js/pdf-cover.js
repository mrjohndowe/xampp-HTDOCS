import * as pdfjsLib from '../vendor/pdf.min.mjs';

pdfjsLib.GlobalWorkerOptions.workerSrc = new URL('../vendor/pdf.worker.min.mjs', import.meta.url).href;

const form = document.getElementById('bookUploadForm');
const bookInput = form?.querySelector('input[name="book"]');
const coverInput = form?.querySelector('input[name="cover"]');
const output = document.getElementById('autoCoverData');
let submitting = false;

form?.addEventListener('submit', async event => {
  if (submitting || coverInput?.files?.length || !bookInput?.files?.length || !output) return;
  const file = bookInput.files[0];
  if (!file.name.toLowerCase().endsWith('.pdf')) return;
  event.preventDefault();
  const submit = form.querySelector('button[type="submit"]');
  try {
    submit.disabled = true;
    submit.textContent = 'Creating page-one cover…';
    const document = await pdfjsLib.getDocument({ data: new Uint8Array(await file.arrayBuffer()) }).promise;
    const page = await document.getPage(1);
    const viewport = page.getViewport({ scale: Math.min(1.6, 900 / page.getViewport({ scale: 1 }).width) });
    const canvas = document.createElement('canvas');
    canvas.width = Math.ceil(viewport.width);
    canvas.height = Math.ceil(viewport.height);
    await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
    output.value = canvas.toDataURL('image/jpeg', .88);
    submitting = true;
    form.submit();
  } catch (error) {
    console.error(error);
    alert('The PDF uploaded, but page one could not be used as its cover. Please choose a cover image and try again.');
    submit.disabled = false;
    submit.textContent = 'Upload Book';
  }
});
