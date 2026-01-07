(function($){
  $(function(){
    const $wrap = $('#gdos-steps');
    const tpl = document.getElementById('gdos-step-template');
    let idx = $wrap.children('.gdos-step-item').length;

    $('#gdos-add-step').on('click', function(){
      const html = tpl.innerHTML.replaceAll('__INDEX__', String(idx++));
      $wrap.append(html);
    });

    $wrap.on('click', '.delete-step', function(){
      $(this).closest('.gdos-step-item').remove();
    });
  });
})(jQuery);
