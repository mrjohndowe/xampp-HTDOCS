import * as pdfjsLib from '../vendor/pdf.min.mjs';
pdfjsLib.GlobalWorkerOptions.workerSrc = new URL('../vendor/pdf.worker.min.mjs', import.meta.url).href;
const button = document.getElementById('makeFirstPageCover');
button?.addEventListener('click', async () => {
  const status = document.getElementById('coverStatus');
  const output = document.getElementById('autoCoverData');
  try {
    button.disabled = true; status.textContent = 'Creating cover from page 1…';
    const doc = await pdfjsLib.getDocument(button.dataset.pdfUrl).promise;
    const page = await doc.getPage(1); const base = page.getViewport({scale: 1});
    const view = page.getViewport({scale: Math.min(1.6, 900 / base.width)});
    const canvas = document.createElement('canvas'); canvas.width = Math.ceil(view.width); canvas.height = Math.ceil(view.height);
    await page.render({canvasContext: canvas.getContext('2d'), viewport: view}).promise;
    output.value = canvas.toDataURL('image/jpeg', .88);
    status.textContent = 'Page-one cover is ready. Click Save Changes.';
  } catch (error) { console.error(error); status.textContent = 'Could not render page 1. Choose an image instead.'; }
  finally { button.disabled = false; }
});
