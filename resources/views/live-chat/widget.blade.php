<div id="customer-chat-widget" class="customer-chat-widget" data-csrf="{{ csrf_token() }}">
    <button type="button" id="customer-chat-toggle" class="customer-chat-launcher" aria-expanded="false" aria-controls="customer-chat-panel">◉ Chat with support</button>
    <section id="customer-chat-panel" class="customer-chat-panel" hidden aria-label="Customer support chat">
        <header><div><strong>HelpDesk Support</strong><small>We are here to help</small></div><button type="button" id="customer-chat-close" aria-label="Close chat">×</button></header>
        <div id="customer-chat-messages" class="customer-chat-messages" aria-live="polite"><p>Start a conversation with our team.</p></div>
        <form id="customer-chat-form"><input id="customer-chat-input" maxlength="5000" placeholder="Write your message…" required><button type="submit">Send</button></form>
    </section>
</div>
<style>
.customer-chat-widget{position:fixed;right:26px;bottom:24px;z-index:1000;font-family:inherit}.customer-chat-launcher{border:0;border-radius:999px;padding:14px 20px;background:var(--primary,#4f46e5);color:#fff;font-weight:700;box-shadow:0 8px 30px #1113;cursor:pointer}.customer-chat-panel{width:min(360px,calc(100vw - 28px));height:470px;max-height:calc(100vh - 110px);background:var(--card-bg,#fff);color:var(--text-primary,#111827);border:1px solid var(--border-color,#ddd);border-radius:16px;box-shadow:0 16px 50px #1113;display:flex;flex-direction:column;overflow:hidden;margin-bottom:12px}.customer-chat-panel[hidden]{display:none}.customer-chat-panel header{padding:15px 16px;background:var(--primary,#4f46e5);color:#fff;display:flex;justify-content:space-between;align-items:center}.customer-chat-panel header small{display:block;opacity:.8;margin-top:3px}.customer-chat-panel header button{border:0;background:transparent;color:#fff;font-size:24px;cursor:pointer}.customer-chat-messages{padding:14px;display:flex;flex:1;flex-direction:column;gap:9px;overflow:auto}.customer-chat-message{padding:10px 12px;background:var(--surface-muted,#f3f4f6);border-radius:12px;max-width:90%;overflow-wrap:anywhere}.customer-chat-message.mine{align-self:flex-end;background:var(--primary-soft,#e0e7ff)}.customer-chat-message small{display:block;opacity:.6;margin-top:5px;font-size:10px}.customer-chat-panel form{display:flex;gap:8px;padding:12px;border-top:1px solid var(--border-color,#ddd)}.customer-chat-panel input{min-width:0;flex:1;padding:10px;border:1px solid var(--border-color,#ddd);border-radius:9px;background:transparent;color:inherit}.customer-chat-panel form button{border:0;border-radius:9px;padding:0 14px;background:var(--primary,#4f46e5);color:#fff}
</style>
<script>
(function(){
 const root=document.getElementById('customer-chat-widget');if(!root)return;
 const csrf=root.dataset.csrf,toggle=document.getElementById('customer-chat-toggle'),panel=document.getElementById('customer-chat-panel'),close=document.getElementById('customer-chat-close'),messages=document.getElementById('customer-chat-messages'),form=document.getElementById('customer-chat-form'),input=document.getElementById('customer-chat-input');
 let conversationId=null,known=new Set();
 const request=async(url,method='GET',body=null)=>{const response=await fetch(url,{method,credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:body?JSON.stringify(body):undefined});if(!response.ok)throw new Error('Chat request failed');return response.json()};
 const el=(tag,cls,value)=>{const n=document.createElement(tag);if(cls)n.className=cls;if(value!==undefined)n.textContent=value;return n};
 async function refresh(){if(!conversationId)return;const data=await request('/live-chat/conversations/'+conversationId+'/messages');(data.messages||[]).forEach(m=>{if(known.has(m.id))return;known.add(m.id);const bubble=el('article','customer-chat-message'+(m.user_id===Number(@json(auth()->id()))?' mine':''));bubble.append(el('div','',m.body));bubble.append(el('small','',m.user+' · '+m.created_at));messages.append(bubble)});messages.scrollTop=messages.scrollHeight}
 async function start(){if(conversationId)return;const data=await request('{{ route('live-chat.start') }}','POST',{});conversationId=data.conversation_id;await refresh()}
 async function open(){panel.hidden=false;toggle.setAttribute('aria-expanded','true');try{await start()}catch(e){messages.textContent='Chat is temporarily unavailable.'}}
 function hide(){panel.hidden=true;toggle.setAttribute('aria-expanded','false')}
 toggle.addEventListener('click',()=>panel.hidden?open():hide());close.addEventListener('click',hide);
 form.addEventListener('submit',async e=>{e.preventDefault();if(!input.value.trim())return;try{await start();const body=input.value.trim();input.value='';await request('/live-chat/conversations/'+conversationId+'/messages','POST',{body});await refresh()}catch(e){messages.append(el('p','','Unable to send your message. Please try again.'))}});
 window.setInterval(()=>{if(!panel.hidden)refresh().catch(()=>{})},2500);
})();
</script>
