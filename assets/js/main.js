// Cursor
const cur=document.getElementById('cursor'),ring=document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{
  mx=e.clientX;my=e.clientY;
  cur.style.left=mx+'px';cur.style.top=my+'px';
});
(function loop(){
  rx+=(mx-rx)*.12;ry+=(my-ry)*.12;
  ring.style.left=rx+'px';ring.style.top=ry+'px';
  requestAnimationFrame(loop);
})();
document.querySelectorAll('a,button,.feat-card,.plat-badge,.s-row,.pill').forEach(el=>{
  el.addEventListener('mouseenter',()=>{cur.style.width='16px';cur.style.height='16px';ring.style.width='52px';ring.style.height='52px';ring.style.opacity='.5'});
  el.addEventListener('mouseleave',()=>{cur.style.width='10px';cur.style.height='10px';ring.style.width='36px';ring.style.height='36px';ring.style.opacity='1'});
});

// Nav
const nav=document.getElementById('nav');
window.addEventListener('scroll',()=>nav.classList.toggle('scrolled',window.scrollY>60));

// Scroll reveal
const obs=new IntersectionObserver(e=>e.forEach(el=>{if(el.isIntersecting)el.target.classList.add('v')}),{threshold:.1,rootMargin:'0px 0px -40px 0px'});
document.querySelectorAll('.reveal,.reveal-l,.reveal-r').forEach(el=>obs.observe(el));

// Smooth links
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click',e=>{
    const t=document.querySelector(a.getAttribute('href'));
    if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth'})}
  });
});

// CTA
document.getElementById('cta-btn').addEventListener('click',function(){
  const inp=document.querySelector('.email-form input');
  if(inp.value&&inp.value.includes('@')){
    this.textContent='✓ Inscrit !';
    inp.value='';
  } else {
    inp.style.outline='1px solid rgba(76,229,174,.5)';
    setTimeout(()=>inp.style.outline='',800);
  }
});

    (function(){
      var phones  = document.querySelectorAll('.c-phone');
      var dots    = document.querySelectorAll('.c-dot');
      var label   = document.querySelector('.c-label-text');
      var labels  = ['Connexion','Bibliothèque','Jeux'];
      var n       = phones.length;
      var cur     = 0;
      var timer;

      function go(to){
        to = ((to % n) + n) % n;
        cur = to;
        phones.forEach(function(p, i){
          var diff = ((i - cur) + n) % n;
          p.className = 'c-phone ' + (diff===0?'c-active':diff===1?'c-next':diff===n-1?'c-prev':'c-hidden');
        });
        dots.forEach(function(d,i){ d.classList.toggle('c-dot-active', i===cur); });
        if(label){
          label.classList.add('c-label-out');
          setTimeout(function(){
            label.textContent = labels[cur];
            label.classList.remove('c-label-out');
          }, 200);
        }
        clearInterval(timer);
        timer = setInterval(function(){ go(cur+1); }, 4000);
      }

      dots.forEach(function(d){ d.addEventListener('click', function(){ go(+d.dataset.idx); }); });

      var stage = document.getElementById('carouselStage');
      var sx = 0;
      stage.addEventListener('touchstart', function(e){ sx = e.touches[0].clientX; }, {passive:true});
      stage.addEventListener('touchend', function(e){
        var dx = e.changedTouches[0].clientX - sx;
        if(Math.abs(dx) > 40) go(cur + (dx < 0 ? 1 : -1));
      });

      go(0);
    })();