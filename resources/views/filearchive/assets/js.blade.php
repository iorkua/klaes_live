<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
  $(document).ready(function() {
    lucide.createIcons();

    const $viewerDialog = $('#document-viewer-dialog');
    let documentRequestToken = 0;

    $('.dialog-backdrop').hide();

    $('.file-card').on('click', function() {
      const $card = $(this);
      const pagesUrl = $card.attr('data-pages-url');
      const meta = {
        number: $card.attr('data-file-number') || '-',
        title: $card.attr('data-file-title') || '-'
      };
      if (!pagesUrl) {
        console.warn('No document pages URL found for file card.');
        return;
      }

      openDocumentViewer(pagesUrl, false, meta);
    });

    $('.dialog-backdrop').on('click', function(e) {
      if ($(e.target).hasClass('dialog-backdrop')) {
        $(this).fadeOut('fast');
      }
    });

    $('#close-details, #close-viewer').on('click', function() {
      $(this).closest('.dialog-backdrop').fadeOut('fast');
    });

    $('#toggle-star').on('click', function() {
      $(this).find('i').toggleClass('fill-yellow-400 text-yellow-400');
    });

    function openDocumentViewer(pagesUrl, triggeredFromDetails = false, meta = null) {
      const requestToken = ++documentRequestToken;

      resetDocumentViewerState(true, meta);
      $viewerDialog.fadeIn('fast');

      $.ajax({
        url: pagesUrl,
        method: 'GET',
        data: { _: Date.now() },
        cache: false
      })
        .done(function(response) {
          if (documentRequestToken !== requestToken) {
            return;
          }

          if (response.success) {
            loadDocumentPages(response.file, response.pages || []);
            $('#viewer-file-number').text(response.file?.file_number || (meta?.number ?? '-'));
            $('#viewer-file-title').text(response.file?.file_title || (meta?.title ?? '-'));
            if (triggeredFromDetails) {
              $('#file-details-dialog').fadeOut('fast');
            }
          } else {
            showViewerError(response.message || 'Unable to load document pages.');
          }
        })
        .fail(function(xhr) {
          if (documentRequestToken !== requestToken) {
            return;
          }

          console.error('Error loading document pages', xhr);
          showViewerError('Failed to load document pages.');
        });
    }

    function resetDocumentViewerState(isLoading = false, meta = null) {
      if (typeof window.clearDocumentViewerData === 'function') {
        window.clearDocumentViewerData();
      }

      $('#pages-list').html(isLoading ? '<div class="p-4 text-sm text-gray-500">Loading document pages...</div>' : '');
      $('#page-info').html(`<span class="font-medium">${isLoading ? 'Loading document...' : 'Select a page'}</span>`);
      $('#page-indicator').text('Page 0 of 0');

      if (meta) {
        $('#viewer-file-number').text(meta.number || '-');
        $('#viewer-file-title').text(meta.title || '-');
      } else {
        $('#viewer-file-number').text('-');
        $('#viewer-file-title').text('-');
      }

      $('#document-image').attr('src', '').addClass('hidden');
      $('#document-pdf').attr('src', '').addClass('hidden');
      $('#document-placeholder').css('display', 'none');

      if (typeof updateTransform === 'function') {
        updateTransform();
      }
    }

    function showViewerError(message) {
      if (typeof window.clearDocumentViewerData === 'function') {
        window.clearDocumentViewerData();
      }
      $('#pages-list').html(`<div class="p-4 text-sm text-red-600">${message}</div>`);
      $('#page-info').html(`<span class="text-red-600">${message}</span>`);
      $('#page-indicator').text('Page 0 of 0');
      $('#viewer-file-number').text('-');
      $('#viewer-file-title').text('-');
      $('#document-image').attr('src', '').addClass('hidden');
      $('#document-pdf').attr('src', '').addClass('hidden');
      $('#document-placeholder').css('display', 'flex');
      const placeholderText = $('#document-placeholder').find('p');
      if (placeholderText.length) {
        placeholderText.text(message);
      }
    }

    window.openDocumentViewer = openDocumentViewer;
  });
</script>