{{-- Unsaved-changes guard modal. Usage: @include('crm.partials.unsaved_guard', ['formSelector' => 'form.my-form']) --}}
@php $ugSelector = $formSelector ?? 'form'; @endphp
<style>
.ug-backdrop{position:fixed;inset:0;z-index:13000;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.46);backdrop-filter:blur(7px)}
.ug-backdrop.is-open{display:flex}
.ug-dialog{width:min(460px,96vw);overflow:hidden;border:1px solid #e6ebf2;border-radius:18px;background:#fff;box-shadow:0 30px 85px rgba(15,23,42,.24);animation:ugPop .18s ease}
@keyframes ugPop{from{transform:translateY(10px) scale(.98);opacity:0}to{transform:none;opacity:1}}
.ug-head{display:flex;gap:.85rem;align-items:flex-start;padding:1.4rem 1.45rem 1rem;background:#fff}
.ug-icon{display:grid;flex:0 0 40px;width:40px;height:40px;place-items:center;border-radius:50%;background:var(--primary-soft,#fff0eb);color:var(--primary-purple,#f45a24);font-size:1rem}
.ug-head h3{margin:.05rem 0 .22rem;color:#0f172a;font-size:1.08rem;letter-spacing:-.015em;font-weight:700}
.ug-sub{color:#94a3b8;font-size:.78rem}
.ug-body{margin:0 1.45rem;padding:1rem 0 1.25rem;border-top:1px solid #edf1f5;color:#64748b;font-size:.82rem;line-height:1.6}
.ug-actions{display:flex;justify-content:flex-end;gap:.55rem;padding:1rem 1.45rem 1.25rem;background:#fff}
.ug-btn{display:inline-flex;align-items:center;gap:.4rem;min-height:42px;padding:0 1.1rem;border-radius:11px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .15s ease}
.ug-btn:hover{background:#f8fafc}
.ug-btn.ug-leave{border-color:#e2e8f0;color:#b42318}
.ug-btn.ug-leave:hover{border-color:#fecaca;background:#fff7f7}
.ug-btn.ug-primary{border:none;background:linear-gradient(135deg,var(--primary-purple,#f45a24),var(--primary-hover,#e04a17));color:#fff;box-shadow:0 4px 14px var(--primary-shadow,rgba(244,90,36,.35))}
.ug-btn.ug-primary:hover{filter:brightness(1.05)}
</style>
<div class="ug-backdrop" id="ugModal" role="dialog" aria-modal="true" aria-labelledby="ugTitle">
    <div class="ug-dialog">
        <div class="ug-head">
            <span class="ug-icon"><i class="fas fa-exclamation"></i></span>
            <div><h3 id="ugTitle">Unsaved changes</h3><div class="ug-sub">Your changes have not been saved yet.</div></div>
        </div>
        <div class="ug-body">Leaving this page now will lose everything you have entered. Are you sure you want to leave without saving?</div>
        <div class="ug-actions">
            <button class="ug-btn ug-leave" type="button" id="ugLeave">Leave</button>
            <button class="ug-btn ug-primary" type="button" id="ugStay"><i class="fas fa-arrow-left"></i> Stay Here</button>
        </div>
    </div>
</div>
<script>
(function(){
    var form=document.querySelector(@json($ugSelector));
    var modal=document.getElementById('ugModal');
    var stayBtn=document.getElementById('ugStay');
    var leaveBtn=document.getElementById('ugLeave');
    if(!form||!modal||!stayBtn||!leaveBtn)return;
    var isDirty=false,isLeaving=false,pendingUrl='';
    function openModal(url){pendingUrl=url||'';modal.classList.add('is-open');document.body.style.overflow='hidden';stayBtn.focus();}
    function closeModal(){modal.classList.remove('is-open');document.body.style.overflow='';pendingUrl='';}
    form.addEventListener('input',function(){if(!isLeaving)isDirty=true;});
    form.addEventListener('change',function(){if(!isLeaving)isDirty=true;});
    form.addEventListener('submit',function(){isLeaving=true;isDirty=false;});
    document.addEventListener('click',function(event){
        if(!isDirty||isLeaving||event.defaultPrevented||event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
        var link=event.target.closest('a[href]');
        if(!link||link.target==='_blank'||link.hasAttribute('download')||link.closest('#ugModal'))return;
        var href=link.getAttribute('href');
        if(!href||href.charAt(0)==='#'||href.indexOf('javascript:')===0)return;
        event.preventDefault();event.stopPropagation();
        openModal(link.href);
    },true);
    stayBtn.addEventListener('click',closeModal);
    leaveBtn.addEventListener('click',function(){
        var url=pendingUrl;isLeaving=true;isDirty=false;closeModal();
        if(url)window.location.href=url;
    });
    modal.addEventListener('click',function(event){if(event.target===modal)closeModal();});
    document.addEventListener('keydown',function(event){if(event.key==='Escape'&&modal.classList.contains('is-open'))closeModal();});
})();
</script>
