(function ($) {

  // ---------- small helper: bind "Read more" buttons inside scope ----------
  function maorBindReadMore(scopeEl) {
    var scope = scopeEl || document;
    scope.querySelectorAll('.maor-reviews-container .maor-review').forEach(function (card) {
      var p = card.querySelector('.maor-text');
      if (!p) return;

      // avoid duplicate button
      if (card.querySelector('.maor-read-more')) return;

      // crude length check; adjust if desired
      if (p.textContent.trim().length > 220) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'maor-read-more';
        btn.textContent = 'Read more';
        btn.addEventListener('click', function () {
          card.classList.toggle('is-expanded');
          btn.textContent = card.classList.contains('is-expanded') ? 'Show less' : 'Read more';
          // after expand/collapse, nudge any visible carousels to recalc width
          if (window.maorInitCarousels) window.maorInitCarousels();
        });
        p.after(btn);
      }
    });
  }

  // ---------- TAB CLICK -> fetch filtered reviews ----------
  $(document).on('click', '.maor-tabs .maor-tab', function (e) {
    e.preventDefault();

    var $tab     = $(this);
    var source   = $tab.data('source');
    var $wrap    = $tab.closest('.maor-tabbed-reviews');
    var $content = $wrap.find('.maor-reviews-content');

    var cfg = {
      limit:        $wrap.data('limit'),
      layout:       $wrap.data('layout'),
      show_avatars: $wrap.data('show-avatars'),
      show_date:    $wrap.data('show-date'),
      show_source:  $wrap.data('show-source'),
      per_row:      $wrap.data('per-row'),
      per_view:     $wrap.data('per-view')
    };

    $tab.addClass('active').siblings('.maor-tab').removeClass('active');
    $content.addClass('maor-loading');

    $.post(maor_ajax.ajax_url, $.extend({
      action: 'maor_filter_reviews',
      nonce:  maor_ajax.nonce,
      source: source
    }, cfg)).done(function (html) {
      $content.removeClass('maor-loading').html(html);

      // 1) re-init carousels in the freshly injected HTML
      if (window.maorInitCarousels) window.maorInitCarousels();

      // 2) let any listeners know content was replaced
      document.dispatchEvent(new CustomEvent('maor:content-updated'));

      // 3) wire up "Read more" inside the new content
      maorBindReadMore($content[0]);

    }).fail(function () {
      $content.removeClass('maor-loading')
        .html('<p class="maor-error">Failed to load reviews.</p>');
    });
  });

  // ---------- LOAD MORE ----------
  $(document).on('click', '.maor-load-more-btn', function (e) {
    e.preventDefault();

    var $btn       = $(this);
    var $container = $btn.closest('.maor-reviews-container');
    var source     = $container.data('source');
    var offset     = parseInt($container.data('offset'), 10) || 0;
    var limit      = parseInt($container.data('limit'), 10)  || 5;

    $btn.prop('disabled', true);

    $.post(maor_ajax.ajax_url, {
      action: 'maor_load_more',
      nonce:  maor_ajax.nonce,
      source: source,
      offset: offset,
      limit:  limit
    }).done(function (res) {
      if (res === 'no_more') {
        $btn.closest('.maor-load-more-container').remove();
        return;
      }

      // insert new cards before the Load More container
      var $insertionPoint = $btn.closest('.maor-load-more-container');
      $insertionPoint.before(res);

      // bump offset so the next click continues
      $container.data('offset', offset + limit);
      $btn.prop('disabled', false);

      // rebind "Read more" on the newly appended cards
      maorBindReadMore($container[0]);

      // nudge carousels to recalc width if we're in a carousel layout
      if (window.maorInitCarousels) window.maorInitCarousels();

    }).fail(function () {
      $btn.prop('disabled', false);
      alert('Could not load more reviews.');
    });
  });

  // initial "Read more" on first paint
  $(function(){ maorBindReadMore(document); });

})(jQuery);


// ---------- Keep the tab strip centered on mobile (unchanged) ----------
(function () {
  function centerActive(row, smooth) {
    var a = row.querySelector('.maor-tab.active');
    if (!a) return;
    a.scrollIntoView({ block: 'nearest', inline: 'center', behavior: smooth ? 'smooth' : 'auto' });
  }
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.maor-tabs').forEach(function (row) {
      centerActive(row, false);
      row.addEventListener('click', function (e) {
        var t = e.target.closest('.maor-tab');
        if (t && row.contains(t)) setTimeout(function () { centerActive(row, true); }, 0);
      });
    });
  });
})();
