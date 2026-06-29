(function(){
  function enhanceTables(){
    document.querySelectorAll('table.responsive').forEach(function(table, idx){
      if(table.dataset.enhanced) return;
      table.dataset.enhanced='1';
      var tbody=table.tBodies[0]; if(!tbody) return;
      var rows=Array.from(tbody.rows);
      if(rows.length<6) return;
      var wrap=table.closest('.table-wrap') || table.parentElement;
      var panel=document.createElement('div');
      panel.className='table-enhancer';
      panel.innerHTML='<div class="enhancer-left"><span class="enhancer-title">Data Table</span><span class="enhancer-count"></span></div><div class="enhancer-tools"><input class="enhancer-search" placeholder="Cari data..."><select class="enhancer-size"><option value="8">8/baris</option><option value="12">12/baris</option><option value="20">20/baris</option><option value="9999">Semua</option></select></div>';
      wrap.parentNode.insertBefore(panel, wrap);
      var pager=document.createElement('div'); pager.className='table-pager'; wrap.parentNode.insertBefore(pager, wrap.nextSibling);
      var search=panel.querySelector('.enhancer-search'), size=panel.querySelector('.enhancer-size'), count=panel.querySelector('.enhancer-count');
      var page=1;
      function text(row){return row.textContent.toLowerCase();}
      function render(){
        var q=(search.value||'').toLowerCase().trim();
        var filtered=rows.filter(function(r){return !q || text(r).indexOf(q)>-1;});
        var per=parseInt(size.value,10)||8; var pages=Math.max(1, Math.ceil(filtered.length/per)); if(page>pages) page=pages;
        rows.forEach(function(r){r.style.display='none';});
        filtered.slice((page-1)*per,page*per).forEach(function(r){r.style.display='';});
        count.textContent=filtered.length+' data';
        pager.innerHTML='';
        if(pages>1){
          var prev=document.createElement('button'); prev.textContent='‹'; prev.disabled=page===1; prev.onclick=function(){page--;render();}; pager.appendChild(prev);
          for(var i=1;i<=pages;i++){ if(i>1 && i<pages && Math.abs(i-page)>1){ if(!pager.querySelector('.dots-'+i)){var d=document.createElement('span');d.textContent='…';d.className='dots-'+i;pager.appendChild(d);} continue; } var b=document.createElement('button'); b.textContent=i; b.className=i===page?'active':''; (function(n){b.onclick=function(){page=n;render();};})(i); pager.appendChild(b); }
          var next=document.createElement('button'); next.textContent='›'; next.disabled=page===pages; next.onclick=function(){page++;render();}; pager.appendChild(next);
        }
      }
      search.addEventListener('input',function(){page=1;render();}); size.addEventListener('change',function(){page=1;render();}); render();
    });
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',enhanceTables); else enhanceTables();
})();
