(function($){
  function currency(amount){
    // Deja el formato base; Woo hará el render final en carrito. Aquí solo indicador.
    try { return new Intl.NumberFormat(undefined, { style:'currency', currency:'UYU' }).format(amount); }
    catch(e){ return amount.toFixed(2); }
  }

  $(function(){
    $('.gdos-combo-root').each(function(){
      const root = this;
      const cfg  = JSON.parse(root.dataset.config || '{}');
      const steps = cfg.steps || [];

      if (!steps.length) return;

      const $stepper = $(root).find('.gdos-combo-stepper');
      const $content = $(root).find('.gdos-combo-content');
      const $prev    = $(root).find('.gdos-prev');
      const $next    = $(root).find('.gdos-next');
      const $finish  = $(root).find('.gdos-finish');
      const $msg     = $(root).find('.gdos-combo-msg');

      const $summaryItems = $(root).find('.gdos-summary-items');
      const $totalAmount  = $(root).find('.gdos-total-amount');

      let current = 0;
      const state = steps.map((s)=>({ // por paso
        items: [] // [{id, name, price, image, qty}]
      }));

      // Render Stepper (chips)
      function renderStepper(){
        $stepper.empty();
        steps.forEach((s, i)=>{
          const chip = $('<div class="gdos-chip"/>')
            .toggleClass('active', i===current)
            .toggleClass('required', s.required ? true:false)
            .text(`${i+1}. ${s.title}`);
          $stepper.append(chip);
        });
      }

      function productCard(stepIndex, p, allowQty){
        const card = $('<div class="gdos-card"/>');
        card.append($('<img class="gdos-thumb">').attr('src', p.image).attr('alt', p.name));
        card.append($('<div class="gdos-name"/>').text(p.name));

        const price = $('<div class="gdos-price"/>')
          .append(document.createTextNode(currency(p.price)))
          .append($('<small/>').html(p.price_html || ''));
        card.append(price);

        let qtyWrap = null;
        if (allowQty){
          qtyWrap = $('<div class="gdos-qty"/>')
            .append($('<span/>').text('Cantidad'))
            .append($('<input type="number" min="1" step="1" value="1" class="gdos-input-qty">'));
          card.append(qtyWrap);
        }

        const btn = $('<button type="button" class="gdos-select">Seleccionar</button>');
        btn.on('click', function(){
          const selected = $(this).hasClass('selected');
          if (!selected) {
            const qty = allowQty ? parseInt(qtyWrap.find('input').val()||'1',10) : 1;
            addItem(stepIndex, p, qty);
            $(this).addClass('selected').text('Quitar');
          } else {
            removeItem(stepIndex, p.id);
            $(this).removeClass('selected').text('Seleccionar');
          }
          refreshSummary();
          validate();
        });

        card.append($('<div class="gdos-actions"/>').append(btn));
        return card;
      }

      function renderStep(index){
        $content.empty();
        const step = steps[index];
        if (!step) return;

        // título del paso + grilla de productos
        const h = $('<h3/>').text(step.title);
        const grid = $('<div class="gdos-products"/>');

        step.products.forEach(p=>{
          const card = productCard(index, p, !!step.allow_qty);
          // marcar seleccionados existentes
          const isSel = state[index].items.find(x=>x.id===p.id);
          if (isSel) {
            card.find('.gdos-select').addClass('selected').text('Quitar');
            if (step.allow_qty) card.find('.gdos-input-qty').val(isSel.qty);
          }
          grid.append(card);
        });

        $content.append(h, grid);
      }

      function addItem(stepIndex, p, qty){
        const arr = state[stepIndex].items;
        const exist = arr.find(x=>x.id===p.id);
        if (exist) {
          exist.qty = qty;
        } else {
          arr.push({ id:p.id, name:p.name, price:p.price, image:p.image, qty: qty || 1 });
        }
      }

      function removeItem(stepIndex, id){
        const arr = state[stepIndex].items;
        const i = arr.findIndex(x=>x.id===id);
        if (i>=0) arr.splice(i,1);
      }

      function refreshSummary(){
        $summaryItems.empty();
        let total = 0;
        state.forEach(s=>{
          s.items.forEach(it=>{
            total += (it.price * it.qty);
            const line = $('<div class="gdos-summary-line"/>')
              .append($('<img class="gdos-summary-thumb">').attr('src', it.image).attr('alt', it.name))
              .append($('<div/>').text(`${it.name} x${it.qty}`))
              .append($('<div class="gdos-summary-price"/>').text(currency(it.price*it.qty)));
            $summaryItems.append(line);
          });
        });
        $totalAmount.text(currency(total));
      }

      function validate(){
        // Habilitar "Finalizar" si todos los pasos obligatorios tienen al menos 1 item
        let ok = true;
        steps.forEach((s, i)=>{
          if (s.required && state[i].items.length===0) ok = false;
        });
        $finish.prop('disabled', !ok);

        // Navegación
        $prev.prop('disabled', current===0);
        $next.toggle(current < steps.length-1);
        $finish.toggle(current === steps.length-1);
      }

      $prev.on('click', function(){
        if (current>0) current--;
        renderStepper(); renderStep(current); validate();
      });

      $next.on('click', function(){
        const step = steps[current];
        if (step.required && state[current].items.length===0) {
          $msg.text(GDOS_COMBO.i18n.required);
          return;
        }
        $msg.text('');
        if (current < steps.length-1) current++;
        renderStepper(); renderStep(current); validate();
      });

      $finish.on('click', function(){
        // Validación final
        for (let i=0;i<steps.length;i++){
          if (steps[i].required && state[i].items.length===0){
            $msg.text(GDOS_COMBO.i18n.required);
            return;
          }
        }
        $msg.text('');

        // Compilar items
        const items = [];
        state.forEach(s=> s.items.forEach(it=> items.push({ id: it.id, qty: it.qty })));

        $.ajax({
          url: GDOS_COMBO.ajax_url,
          type: 'POST',
          dataType: 'json',
          data: { action:'gdos_combo_add_to_cart', nonce: GDOS_COMBO.nonce, items: items },
          success: function(res){
            if (res && res.success) {
              window.location.href = res.data.redirect || window.location.href;
            } else {
              $msg.text((res && res.data && res.data.message) || GDOS_COMBO.i18n.error);
            }
          },
          error: function(){
            $msg.text(GDOS_COMBO.i18n.error);
          }
        });
      });

      // Init
      renderStepper();
      renderStep(current);
      refreshSummary();
      validate();
    });
  });
})(jQuery);
