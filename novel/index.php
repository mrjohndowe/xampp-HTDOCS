<?php require_once __DIR__ . '/partials.php'; page_header('Library','library'); ?>
<section class="hero">
 <div><div class="eyebrow">YOUR PERSONAL STORY COLLECTION</div><h1>A bookshelf that actually feels like books.</h1><p>Browse visible binders on the shelf. Click one and the book slides out into the center of the screen, reveals the front cover, flips to the back description, or opens so you can start reading.</p></div>
</section>
<section class="shelf-panel panel">
 <div class="shelf-header"><div><h2>Bookshelf</h2><p>Read, rate, and review your collection.</p></div><button id="refreshBtn" class="secondary">Refresh</button></div>
 <div id="bookGrid" class="book-grid"><div class="empty-state">Loading bookshelf…</div></div>
</section>

<section id="bookPreviewModal" class="book-preview-modal hidden" aria-hidden="true">
  <div class="book-preview-backdrop" data-close-preview></div>
  <div class="book-preview-shell" role="dialog" aria-modal="true" aria-labelledby="previewTitle">
    <button id="closePreviewBtn" class="preview-close" aria-label="Close book preview">×</button>
    <div class="book-preview-stage-modal">
      <div class="book-preview-book modal-book" id="previewBook3d">
        <div class="book-preview-inner" id="previewBookInner">
          <div class="book-preview-side book-preview-front">
            <img id="previewCoverImage" src="" alt="" class="book-cover-image hidden">
            <div id="previewCoverFallback" class="book-cover-fallback hidden"></div>
            <span class="book-glint"></span>
            <div class="preview-cover-meta"><strong id="previewTitle">Title</strong><span id="previewAuthor">Author</span></div>
            <div class="preview-rating-badge" id="previewBadge">★ 0.0</div>
          </div>
          <div class="book-preview-side book-preview-back">
            <span class="preview-back-kicker">BACK COVER</span>
            <h3 id="previewBackTitle">Title</h3>
            <p class="preview-back-author" id="previewBackAuthor">by Author</p>
            <p class="preview-back-description" id="previewDescription">Description</p>
            <div class="preview-back-rating"><span id="previewStars">☆☆☆☆☆</span><strong id="previewRatingValue">0.0</strong><small id="previewReviewCount">0 reviews</small></div>
            <a class="secondary-link inline-review-link" id="previewDetailsLink" href="#">View details & reviews</a>
          </div>
        </div>
      </div>
      <div class="book-preview-meta modal-meta">
        <h3 class="book-title" id="previewMetaTitle">Title</h3>
        <p class="book-author" id="previewMetaAuthor">Author</p>
        <div class="card-rating"><span id="previewMetaStars">☆☆☆☆☆</span><strong id="previewMetaRating">0.0</strong><small id="previewMetaCount">0 reviews</small></div>
        <div class="book-actions preview-actions modal-actions">
          <button id="openPreviewBookBtn">Open Book</button>
          <button class="secondary" id="flipPreviewBtn">Show Back</button>
          <a class="secondary-link" id="previewReviewsBtn" href="#">Read Reviews</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="readerModal" class="reader-modal hidden" aria-hidden="true">
 <div class="reader-shell">
  <div class="reader-topbar">
   <div class="reader-meta"><span class="reader-kicker">NOW READING</span><h3 id="readerTitle">Reader</h3><span id="pageIndicator">Page 0 / 0</span></div>
   <div class="reader-controls"><button id="zoomOutBtn" class="secondary" title="Zoom out">A−</button><button id="zoomInBtn" class="secondary" title="Zoom in">A+</button><button id="prevBtn" class="secondary">◀ Prev</button><button id="nextBtn" class="secondary">Next ▶</button><button id="closeReaderBtn">Close</button></div>
  </div>
  <div class="reader-stage" id="readerStage">
   <div class="book-scene">
    <div class="book-reader" id="bookReader">
      <div class="book-spine"></div>
      <div class="book-page book-page-left" id="leftPage"><canvas id="leftCanvas"></canvas><div class="blank-page" id="leftBlank"></div></div>
      <div class="book-page book-page-right" id="rightPage"><canvas id="rightCanvas"></canvas><div class="blank-page" id="rightBlank"></div></div>
      <div class="flip-sheet" id="flipSheet" aria-hidden="true"><div class="flip-face flip-front"><canvas id="flipFrontCanvas"></canvas></div><div class="flip-face flip-back"><canvas id="flipBackCanvas"></canvas></div></div>
    </div>
   </div>
  </div>
  <div class="reader-footer"><input id="pageScrubber" type="range" min="1" max="1" value="1"><span class="reader-tip">Tip: use ← → or click the page edges</span></div>
 </div>
</section>
<?php page_footer(true); ?>
