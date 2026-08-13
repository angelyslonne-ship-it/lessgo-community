const API = "../backend/api/index.php";
const $ = (s, r=document)=>r.querySelector(s);
const $$ = (s, r=document)=>[...r.querySelectorAll(s)];

function esc(v){return String(v??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
function apiUrl(resource, params={}){const u=new URL(API,location.href);u.searchParams.set('resource',resource);Object.entries(params).forEach(([k,v])=>u.searchParams.set(k,v));return u}
async function getData(resource,params={}){const r=await fetch(apiUrl(resource,params)); if(!r.ok) throw new Error('API'); return r.json();}
function nav(){const b=$('.menu'),n=$('.nav-links'); if(b)b.onclick=()=>n.classList.toggle('show'); $$('.nav-links a').forEach(a=>a.addEventListener('click',()=>n.classList.remove('show')));}
function footer(){const y=$('#year');if(y)y.textContent=new Date().getFullYear();}
function counter(){ $$('.counter').forEach(el=>{let target=+el.dataset.target||0;let start=0;let step=Math.max(1,Math.ceil(target/60));let t=setInterval(()=>{start+=step;if(start>=target){start=target;clearInterval(t)}el.textContent=start},20)})}
function showModal(src){const m=$('.modal'),i=$('.modal img');if(m&&i){i.src=src;m.classList.add('show');m.onclick=()=>m.classList.remove('show')}}
async function loadHome(){
  try{
    const d=await getData('home');
    const s=d.s||{};
    const heroTitle=$('#hero-title'); if(heroTitle) heroTitle.innerHTML=esc(s.hero_title||'Développez votre potentiel.').replace('compétences','<span>compétences</span>');
    const heroText=$('#hero-text'); if(heroText) heroText.textContent=s.hero_text||'';
    const phone=$('#phone'); if(phone) phone.textContent=s.phone||'';
    const address=$('#address'); if(address) address.textContent=s.address||'';
    const cards=$('#formation-list');
    if(cards) cards.innerHTML=d.formations.map(f=>`<article class="card"><img class="card-img" src="../backend/uploads/${esc(f.image||'flyer.png')}" onerror="this.src='assets/images/flyer.png'"><div class="card-body"><span class="pill">${esc(f.level||'Formation')}</span><h3>${esc(f.title)}</h3><p>${esc(f.short_description||'')}</p><div class="meta"><span>${esc(f.duration||'Programme flexible')}</span></div><a class="btn btn-primary" href="formation.html?slug=${encodeURIComponent(f.slug)}">Découvrir <span>→</span></a></div></article>`).join('');
    const gal=$('#home-gallery'); if(gal) gal.innerHTML=d.gallery.map(g=>`<img src="../backend/uploads/${esc(g.image)}" alt="${esc(g.title)}" onclick="showModal(this.src)">`).join('');
    const q=$('#testimonial-list'); if(q) q.innerHTML=d.testimonials.slice(0,3).map(t=>`<div class="quote"><div class="stars">${'★'.repeat(Math.max(1,Math.min(5,+t.rating||5)))}</div><p>“${esc(t.message)}”</p><strong>${esc(t.name)}</strong><br><small>${esc(t.role||'')}</small></div>`).join('');
  }catch(e){console.warn(e)}
}
async function loadFormations(){
 const list=$('#formation-list'); if(!list)return;
 try{const data=await getData('formations'); const q=$('#formation-search'); const render=(arr)=>list.innerHTML=arr.map(f=>`<article class="card"><img class="card-img" src="../backend/uploads/${esc(f.image||'flyer.png')}" onerror="this.src='assets/images/flyer.png'"><div class="card-body"><span class="pill">${esc(f.level||'Tous niveaux')}</span><h3>${esc(f.title)}</h3><p>${esc(f.short_description||'')}</p><div class="meta"><span>⏱ ${esc(f.duration||'Programme flexible')}</span></div><a class="btn btn-primary" href="formation.html?slug=${encodeURIComponent(f.slug)}">Voir le programme →</a></div></article>`).join(''); render(data); if(q)q.oninput=()=>render(data.filter(f=>(f.title+' '+f.short_description).toLowerCase().includes(q.value.toLowerCase())));}
 catch(e){list.innerHTML='<p>Impossible de charger les formations.</p>'}
}
async function loadDetail(){
 const box=$('#detail'); if(!box)return;
 const slug=new URLSearchParams(location.search).get('slug'); if(!slug){box.innerHTML='<p>Formation introuvable.</p>';return}
 try{const f=await getData('formations',{slug}); box.innerHTML=`<div><img src="../backend/uploads/${esc(f.image||'flyer.png')}" onerror="this.src='assets/images/flyer.png'"></div><div><span class="pill">${esc(f.level||'')}</span><h1>${esc(f.title)}</h1><p>${esc(f.description||f.short_description||'')}</p><div class="meta"><span>⏱ ${esc(f.duration||'Programme flexible')}</span></div><h3>Compétences</h3><p>${esc(f.skills||'')}</p><h3>Débouchés</h3><p>${esc(f.outcomes||'')}</p><a class="btn btn-orange" href="inscription.html?formation=${encodeURIComponent(f.title)}">S’inscrire à cette formation →</a></div>`; const t=$('#detail-title');if(t)t.textContent=f.title;}
 catch(e){box.innerHTML='<p>Impossible de charger cette formation.</p>'}
}

async function loadGallery(){
 const box=$('#gallery'); if(!box)return;
 try{
   const data=await getData('gallery');
   if(!data.length){box.innerHTML='<p>Aucune image publiée pour le moment.</p>';return}
   box.innerHTML=data.map(g=>`<figure class="gallery-item"><img src="../backend/uploads/${esc(g.image)}" alt="${esc(g.title)}" onclick="showModal(this.src)"><figcaption>${esc(g.title)}<small>${esc(g.category||'')}</small></figcaption></figure>`).join('');
 }catch(e){console.warn(e)}
}
async function loadRegistrationOptions(){
 const sel=$('[name="formation"]'); if(!sel)return;
 try{
   const data=await getData('formations');
   const wanted=new URLSearchParams(location.search).get('formation');
   sel.innerHTML='<option value="">Choisir une formation</option>'+data.map(f=>`<option value="${esc(f.title)}">${esc(f.title)}</option>`).join('');
   if(wanted)sel.value=wanted;
 }catch(e){console.warn(e)}
}

async function submitForm(form,resource,target){
 form.addEventListener('submit',async e=>{e.preventDefault(); const btn=form.querySelector('button[type=submit]')||form.querySelector('button'); if(btn)btn.disabled=true;
 const data=Object.fromEntries(new FormData(form).entries()); try{const r=await fetch(apiUrl(resource),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});const d=await r.json(); if(!r.ok)throw new Error(d.error||'Erreur'); target.innerHTML=`<div class="notice success">${esc(d.message||'Opération réussie.')}</div>`;form.reset()}catch(err){target.innerHTML=`<div class="notice error">${esc(err.message)}</div>`}finally{if(btn)btn.disabled=false}})
}
document.addEventListener('DOMContentLoaded',()=>{nav();footer();counter();loadHome();loadFormations();loadDetail();loadGallery();loadRegistrationOptions(); const reg=$('#register-form'),msg=$('#contact-form'),out=$('#form-result');if(reg)submitForm(reg,'register',out||document.createElement('div'));if(msg)submitForm(msg,'contact',out||document.createElement('div'));const f=new URLSearchParams(location.search).get('formation');const sel=$('[name="formation"]');if(f&&sel)sel.value=f; $$('.gallery img').forEach(i=>i.onclick=()=>showModal(i.src));});
