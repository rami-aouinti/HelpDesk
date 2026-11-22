@extends('layouts.app')

@section('title', 'Live Chat')
@section('page-title', 'Live Chat')

@section('content')
<div class="page-header"><div><h1>{{ $isAgent ? 'Live Chat Inbox' : 'Chat with support' }}</h1><p>{{ $isAgent ? 'Pick up customer conversations and reply in real time.' : 'Start a conversation with our support team. Messages refresh automatically.' }}</p></div></div>
<div class="live-chat-shell" data-agent="{{ $isAgent ? '1' : '0' }}"
 data-conversations-url="{{ route('live-chat.conversations') }}"
 data-start-url="{{ route('live-chat.start') }}"
 data-agents-url="{{ route('live-chat.agents') }}"
 data-presence-url="{{ route('live-chat.presence') }}"
 data-csrf="{{ csrf_token() }}"
 data-user-id="{{ auth()->id() }}">
    <aside class="live-chat-sidebar">
        @if ($isAgent)
            <label>My status<select id="agent-presence"><option value="available">Available</option><option value="busy">Busy</option><option value="offline">Offline</option></select></label>
        @else
            <button id="start-chat" class="btn btn-primary">Start a conversation</button>
            <div id="available-agents" class="chat-agent-presence"></div>
        @endif
        <h2>{{ $isAgent ? 'Open conversations' : 'Your conversations' }}</h2>
        <div id="chat-conversations" class="chat-conversation-list"><p>Loading conversations…</p></div>
    </aside>
    <section class="live-chat-main">
        <div id="chat-empty" class="chat-empty-state"><strong>Select a conversation</strong><span>Your messages will appear here.</span></div>
        <div id="chat-active" hidden>
            <header class="chat-active-header"><div><strong id="chat-title">Conversation</strong><small id="chat-status"></small></div><div id="chat-actions"></div></header>
            <div id="chat-messages" class="chat-message-list" aria-live="polite"></div>
            <form id="chat-send-form" class="chat-compose"><input id="chat-message-input" maxlength="5000" placeholder="Write a message…" autocomplete="off" required><button class="btn btn-primary">Send</button></form>
        </div>
    </section>
</div>
<style>
.live-chat-shell{display:grid;grid-template-columns:minmax(240px,320px) minmax(0,1fr);min-height:620px;background:var(--card-bg,#fff);border:1px solid var(--border-color,#e5e7eb);border-radius:16px;overflow:hidden}
.live-chat-sidebar{padding:20px;border-right:1px solid var(--border-color,#e5e7eb)}.live-chat-sidebar h2{font-size:15px;margin:22px 0 12px}.live-chat-sidebar label{display:grid;gap:6px}.live-chat-sidebar select,.chat-compose input{width:100%;padding:11px;border:1px solid var(--border-color,#ddd);border-radius:9px;background:transparent;color:inherit}
.chat-conversation-list{display:grid;gap:8px}.chat-conversation-item{padding:12px;border:1px solid var(--border-color,#e5e7eb);border-radius:10px;text-align:left;background:transparent;color:inherit;cursor:pointer}.chat-conversation-item.active{border-color:var(--primary,#6366f1);background:var(--surface-muted,#f5f5f5)}.chat-conversation-item small{display:block;opacity:.65;margin-top:5px}
.live-chat-main{min-width:0;display:flex;flex-direction:column}.chat-empty-state{margin:auto;display:grid;text-align:center;gap:8px;opacity:.65}.chat-active-header{padding:18px 22px;border-bottom:1px solid var(--border-color,#e5e7eb);display:flex;justify-content:space-between;align-items:center}.chat-active-header small{display:block;opacity:.65;margin-top:4px}
.chat-message-list{flex:1;min-height:420px;max-height:620px;overflow:auto;padding:22px;display:flex;flex-direction:column;gap:12px}.chat-message{max-width:min(75%,560px);padding:11px 14px;border-radius:14px;background:var(--surface-muted,#f3f4f6);align-self:flex-start;overflow-wrap:anywhere}.chat-message.mine{align-self:flex-end;background:var(--primary-soft,#e0e7ff)}.chat-message small{display:block;opacity:.6;margin-top:6px;font-size:11px}.chat-compose{padding:16px;border-top:1px solid var(--border-color,#e5e7eb);display:flex;gap:10px}.chat-agent-presence{margin-top:18px;font-size:13px;opacity:.75}
@media(max-width:760px){.live-chat-shell{grid-template-columns:1fr}.live-chat-sidebar{border-right:0;border-bottom:1px solid var(--border-color,#e5e7eb)}.chat-conversation-list{grid-template-columns:repeat(2,minmax(0,1fr))}.chat-message-list{min-height:360px}}
</style>
<script>
(function(){
 const root=document.querySelector('.live-chat-shell');if(!root)return;
 const agent=root.dataset.agent==='1',csrf=root.dataset.csrf,userId=Number(root.dataset.userId);
 const list=document.getElementById('chat-conversations'),empty=document.getElementById('chat-empty'),active=document.getElementById('chat-active'),messages=document.getElementById('chat-messages'),form=document.getElementById('chat-send-form'),input=document.getElementById('chat-message-input');
 let current=null,lastMessageId=0,conversationCache=[];
 const request=async(url,method='GET',body=null)=>{const response=await fetch(url,{method,credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:body?JSON.stringify(body):undefined});if(!response.ok)throw new Error((await response.json().catch(()=>({message:'Request failed'}))).message||'Request failed');return response.json()};
 function node(tag,cls,text){const el=document.createElement(tag);if(cls)el.className=cls;if(text!==undefined)el.textContent=text;return el}
 async function refreshConversations(){const data=await request(root.dataset.conversationsUrl);conversationCache=data.conversations||[];list.replaceChildren();if(!conversationCache.length){list.append(node('p','',agent?'No open conversations.':'No conversations yet.'));return}
 conversationCache.forEach(c=>{const b=node('button','chat-conversation-item'+(current===c.id?' active':''));b.type='button';b.append(node('strong','',c.subject||'Customer conversation'));b.append(node('small','',agent?(c.customer||'Customer')+' · '+c.status:c.status));b.addEventListener('click',()=>openConversation(c.id));list.append(b)})}
 async function openConversation(id){current=id;lastMessageId=0;empty.hidden=true;active.hidden=false;const c=conversationCache.find(x=>x.id===id);document.getElementById('chat-title').textContent=c?.subject||'Conversation';document.getElementById('chat-status').textContent=c?.status||'Open';await refreshMessages();await refreshConversations()}
 async function refreshMessages(){if(!current)return;const data=await request('/live-chat/conversations/'+current+'/messages');const existing=new Set([...messages.children].map(el=>Number(el.dataset.id)));(data.messages||[]).forEach(m=>{if(existing.has(m.id))return;const item=node('article','chat-message'+(m.user_id===userId?' mine':''));item.dataset.id=m.id;item.append(node('div','',m.body));item.append(node('small','',m.user+' · '+m.created_at));messages.append(item);lastMessageId=Math.max(lastMessageId,m.id)});messages.scrollTop=messages.scrollHeight;document.getElementById('chat-status').textContent=data.conversation.status==='open'?'Open conversation':data.conversation.status}
 form.addEventListener('submit',async e=>{e.preventDefault();if(!current||!input.value.trim())return;const body=input.value.trim();input.value='';await request('/live-chat/conversations/'+current+'/messages','POST',{body});await refreshMessages()});
 if(!agent){document.getElementById('start-chat').addEventListener('click',async()=>{const data=await request(root.dataset.startUrl,'POST',{});await refreshConversations();await openConversation(data.conversation_id)});request(root.dataset.agentsUrl).then(data=>{const target=document.getElementById('available-agents');target.textContent=(data.agents||[]).length?data.agents.map(a=>a.name+' · '+a.status).join(' | '):'No agents currently online.'}).catch(()=>{})}
 else{
   const presence=document.getElementById('agent-presence');presence.addEventListener('change',()=>request(root.dataset.presenceUrl,'POST',{status:presence.value}));request(root.dataset.presenceUrl,'POST',{status:'available'}).catch(()=>{});
 }
 refreshConversations().then(()=>{if(!agent&&conversationCache.length)openConversation(conversationCache[0].id)}).catch(()=>{list.textContent='Unable to load conversations.'});
 window.setInterval(()=>{refreshConversations().catch(()=>{});refreshMessages().catch(()=>{})},2500);
 window.addEventListener('beforeunload',()=>{if(agent)navigator.sendBeacon(root.dataset.presenceUrl,new Blob([JSON.stringify({status:'offline',_token:csrf})],{type:'application/json'}))});
})();
</script>
@endsection
