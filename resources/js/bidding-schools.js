function initializeSchools(root) {
    const form = root.closest('form');
    const readonly = root.dataset.readonly === '1';
    const planInput = root.querySelector('[data-school-plan]');
    const tokenInput = root.querySelector('[data-school-token]');
    let plan = JSON.parse(root.querySelector('[data-school-initial]').textContent);
    if (planInput?.value) { try { plan = JSON.parse(planInput.value); } catch {} }
    let token = tokenInput?.value || '';
    let counts = Object.fromEntries(plan.packages.map(p => [p.key, p.assignments_count || 0]));
    let busy = false;
    const dialog = root.querySelector('dialog');
    const content = root.querySelector('[data-school-dialog-content]');
    const message = root.querySelector('[data-school-message]');
    const error = root.querySelector('[data-school-dialog-error]');
    const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const button = (action, label, extra = '') => `<button type="button" data-school-action="${action}" ${extra} class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50">${escape(label)}</button>`;
    const field = (key, label, value = '', type = 'text') => `<label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">${escape(label)}<input data-edit="${key}" type="${type}" value="${escape(value)}" ${type === 'number' ? 'min="0" step="any"' : ''} class="min-w-0 rounded-lg border-slate-200 text-sm font-normal"></label>`;
    const packageLabel = p => `${p.lot_no} / ${p.stage} / ${p.grade_alias} / ${p.code}`;
    const options = selected => plan.packages.map(p => `<option value="${p.key}" ${p.key === selected ? 'selected' : ''}>${escape(packageLabel(p))}</option>`).join('');
    const selection = (key = '') => `<label class="block text-xs font-semibold">Lot / key stage / package<select data-package-select class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option value="">Select a package</option>${options(key)}</select></label>`;
    const saveDraft = () => {
        if (!planInput) return;
        planInput.disabled = tokenInput.disabled = false;
        planInput.value = JSON.stringify(plan);
        tokenInput.value = token;
    };
    async function api(action, data = {}, file = null, raw = false) {
        const payload = { action, token, project_id: root.dataset.projectId || null, ...data };
        let body;
        const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || form?.querySelector('[name="_token"]')?.value || '' };
        if (file) {
            body = new FormData();
            for (const [key, value] of Object.entries(payload)) if (value !== null) body.append(key, value);
            body.append('file', file);
        } else { headers['Content-Type'] = 'application/json'; body = JSON.stringify(payload); }
        const response = await fetch(root.dataset.endpoint, { method: 'POST', headers, body, credentials: 'same-origin' });
        if (!response.ok) {
            const json = await response.json().catch(() => ({}));
            throw new Error(Object.values(json.errors || {}).flat().join(' ') || json.message || 'The request failed. Reload and try again.');
        }
        return raw ? response.blob() : response.json();
    }
    async function begin() {
        if (!token) { token = (await api('begin', {revision: root.dataset.revision || null})).token; }
    }
    function open(title, html) {
        root.querySelector('[data-school-dialog-title]').textContent = title;
        content.innerHTML = html;
        error.textContent = '';
        if (!dialog.open) dialog.showModal();
    }
    function money(p) {
        const cents = v => { const [w, f = ''] = String(v || 0).split('.'); return BigInt(w || 0) * 100n + BigInt(f.padEnd(2, '0').slice(0, 2)); };
        try {
            return p.items.reduce((sum, i) => sum + (cents(i.quantity) * cents(i.unit_cost) * BigInt(p.quantity_basis === 'per_school' ? counts[p.key] || 0 : 1) + 50n) / 100n, 0n);
        } catch { return 0n; }
    }
    function render() {
        root.querySelector('[data-school-packages]').innerHTML = plan.packages.length ? plan.packages.map(p => `<article class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4"><div class="flex flex-wrap justify-between gap-2"><h4 class="text-sm font-semibold">${escape(packageLabel(p))}</h4><span class="text-xs text-blue-700">${counts[p.key] || 0} assignments</span></div><p class="text-xs text-slate-500">${p.items.length} items · Quantities ${p.quantity_basis === 'per_school' ? 'per school' : 'for the whole lot'} · PHP ${(Number(money(p)) / 100).toLocaleString('en-PH', {minimumFractionDigits:2})}</p><details class="text-xs text-slate-600"><summary class="cursor-pointer">Package items &amp; logistics</summary><ul class="mt-2 space-y-2">${p.items.map(i=>`<li>${escape(i.item_description)} ? ${escape(i.quantity)} ${escape(i.unit)} ? PHP ${escape(i.unit_cost)}<br>${escape(i.brand)} ${escape(i.remarks)}</li>`).join('')}</ul><p class="mt-2">${escape(p.cartons)} cartons ? ${escape(p.cbm)} CBM ? gross weight ${escape(p.gross_weight)} ? volumetric weight ${escape(p.volumetric_weight)}</p></details><div class="flex flex-wrap gap-2">${readonly ? '' : button('edit-package', 'Configure items', `data-key="${p.key}"`)}${button('list', 'View assigned schools', `data-key="${p.key}"`)}${readonly ? '' : button('remove-package', 'Remove package', `data-key="${p.key}"`)}</div></article>`).join('') : '<p class="text-sm text-slate-500">Create a lot, then configure its key stages and packages to assign schools.</p>';
        root.querySelector('[data-school-count]').textContent = `${Object.values(counts).reduce((a,b)=>a+b,0)} assignments`;
        if (form) {
            for (const lot of form.querySelectorAll('[data-bidding-lot]')) {
                const name = lot.querySelector('[data-lot-number]').value;
                lot.dataset.schoolTotal = String(plan.packages.filter(p=>p.lot_no === name).reduce((sum,p)=>sum+money(p),0n));
            }
            form.dispatchEvent(new Event('school-totals'));
        }
    }
    let editingKey;
    function itemEditor(item = {}) {
        return `<div data-package-item class="space-y-3 rounded-lg border border-slate-200 p-3"><div class="flex flex-wrap items-end gap-2"><label class="flex-1 text-xs font-semibold">Find catalog item<input data-item-search class="mt-1 w-full rounded-lg border-slate-200 text-sm" placeholder="Item name or code"></label>${button('catalog', 'Search')}</div><select data-edit="catalog_item_id" class="w-full rounded-lg border-slate-200 text-sm"><option value="${escape(item.catalog_item_id || '')}">${escape(item.item_description || 'Select a catalog item')}</option></select><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">${field('item_description','Description',item.item_description)}${field('unit','Unit',item.unit)}${field('quantity','Quantity',item.quantity || '1','number')}${field('unit_cost','Unit cost (PHP)',item.unit_cost || '0','number')}${field('brand','Brand / specifications',item.brand)}${field('remarks','Remarks',item.remarks)}</div>${button('remove-item','Remove item')}</div>`;
    }
    function editPackage(key) {
        editingKey = key;
        const p = plan.packages.find(p=>p.key === key) || {items:[], cartons:0,cbm:0,volumetric_weight:0,gross_weight:0};
        const lots = [...(form?.querySelectorAll('[data-lot-number]') || [])].map(e=>e.value).filter(Boolean);
        if (!lots.length) throw new Error('Add and name a lot first.');
        open('Configure key stage & package', `<div data-package-fields class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"><label class="text-xs font-semibold">Lot<select data-edit="lot_no" class="mt-1 w-full rounded-lg border-slate-200 text-sm">${lots.map(l=>`<option ${l===p.lot_no?'selected':''}>${escape(l)}</option>`).join('')}</select></label>${field('stage','Key stage (for example KS1)',p.stage)}${field('grade_alias','Masterlist grade (for example G1toG3)',p.grade_alias)}${field('code','Package code (for example C1)',p.code)}<label class="text-xs font-semibold">Quantity basis<select data-edit="quantity_basis" class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option value="">Choose how quantities are priced</option><option value="per_school" ${p.quantity_basis==='per_school'?'selected':''}>Per school × assigned schools</option><option value="lot_total" ${p.quantity_basis==='lot_total'?'selected':''}>Whole lot; do not multiply</option></select></label>${field('cartons','Cartons per package',p.cartons,'number')}${field('cbm','CBM per package',p.cbm,'number')}${field('volumetric_weight','Volumetric weight',p.volumetric_weight,'number')}${field('gross_weight','Gross weight',p.gross_weight,'number')}</div><div data-package-items class="space-y-3">${p.items.map(itemEditor).join('')}</div><div class="flex gap-2">${button('add-item','+ Add catalog item')}${button('save-package','Apply package configuration')}</div>`);
    }
    async function listing(page = 1, key = '', search = '') {
        const result = await api('list', {page, package_key:key || null, search});
        counts = result.counts;
        render();
        open('Assigned schools', `${selection(key)}<div class="flex gap-2"><input data-assignment-search value="${escape(search)}" placeholder="School ID or school name" class="min-w-0 flex-1 rounded-lg border-slate-200 text-sm">${button('filter','Search')}</div><p class="text-xs text-slate-500">${result.total} assignments · Page ${page}</p><div class="space-y-3">${result.rows.map(r=>`<article class="rounded-lg border border-slate-200 p-3"><p class="text-sm font-semibold">${escape(r.school_id)} — ${escape(r.school?.school_name)}</p><p class="mt-1 text-xs text-slate-600">${escape([r.school?.region,r.school?.division,r.school?.municipality,r.school?.address].filter(Boolean).join(' / '))}</p><p class="mt-1 text-xs text-blue-700">${escape(packageLabel(plan.packages.find(p=>p.key===r.package_key) || {}))}</p>${readonly?'':button('change-assignment','Change package / key stage',`data-key="${r.package_key}" data-school="${escape(r.school_id)}"`)}${readonly?'':button('remove-assignment','Remove assignment',`data-key="${r.package_key}" data-school="${escape(r.school_id)}"`)}</article>`).join('')}</div><div class="flex gap-2">${page>1?button('page','Previous',`data-page="${page-1}"`):''}${page*25<result.total?button('page','Next',`data-page="${page+1}"`):''}</div>`);
    }
    let pendingToken = '';
    let reassignment = null;
    async function preview(data) {
        const result = await api('preview',{school_plan:JSON.stringify(plan),...data});
        pendingToken = result.token;
        open('Review school assignments', `<dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">${Object.entries(result.summary).filter(([k,v])=>typeof v === 'number').map(([k,v])=>`<div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs capitalize text-slate-500">${escape(k)}</dt><dd class="mt-1 text-xl font-semibold">${v}</dd></div>`).join('')}</dl><div class="space-y-2">${Object.entries(result.summary.lots || {}).map(([lot,stats])=>`<p class="text-xs">${escape(lot)}: ${stats.schools} schools / ${stats.assignments} assignments</p>`).join('')}</div><p class="text-xs text-slate-600">Review additions and removals before confirming. Changes take effect when you save the bidding document.</p>${result.errors.length?`<ul class="space-y-1 text-xs text-red-700">${result.errors.map(e=>`<li>Row ${e.row}: ${escape(e.school_id)} — ${escape(e.problem)}</li>`).join('')}</ul>`:''}<div class="flex flex-wrap gap-2">${button('errors','Download error report')}${button('confirm','Confirm valid assignments')}${button('close','Cancel')}</div>`);
    }
    root.addEventListener('click', async event => {
        const target = event.target.closest('[data-school-action]');
        if (!target || busy) return;
        const action = target.dataset.schoolAction;
        if (action === 'close') { dialog.close(); return; }
        error.textContent = message.textContent = '';
        busy = true;
        target.disabled = true;
        try {
            await begin();
            if (action === 'package' || action === 'edit-package') editPackage(target.dataset.key);
            else if (action === 'add-item') content.querySelector('[data-package-items]').insertAdjacentHTML('beforeend',itemEditor());
            else if (action === 'remove-item') target.closest('[data-package-item]').remove();
            else if (action === 'catalog') {
                const row=target.closest('[data-package-item]');
                const url=new URL(root.dataset.catalogUrl,location.href);url.searchParams.set('q',row.querySelector('[data-item-search]').value);
                const response=await fetch(url,{headers:{Accept:'application/json'}});if(!response.ok) throw new Error('Catalog search failed.');
                const json=await response.json();const select=row.querySelector('select');
                select.innerHTML='<option value="">Select a catalog item</option>';
                for(const item of json.items){const option=new Option(item.item_name,item.id);option.dataset.item=JSON.stringify(item);select.add(option);}
            }
            else if (action === 'save-package') {
                const values = container => Object.fromEntries([...container.querySelectorAll('[data-edit]')].map(e=>[e.dataset.edit,e.value]));
                const packageData = {...values(content.querySelector('[data-package-fields]')),key:editingKey || crypto.randomUUID(), items:[...content.querySelectorAll('[data-package-item]')].map(values)};
                if (!packageData.quantity_basis || !packageData.stage || !packageData.grade_alias || !packageData.code || !packageData.items.length || packageData.items.some(i=>!i.catalog_item_id)) throw new Error('Choose a quantity basis, stage, grade, code and at least one catalog item.');
                const index=plan.packages.findIndex(p=>p.key===editingKey);
                if(index<0)plan.packages.push(packageData);else plan.packages[index]=packageData;
                saveDraft();render();dialog.close();
            }
            else if (action === 'remove-package') {
                if(counts[target.dataset.key]) throw new Error('Remove or reassign this package’s schools first.');
                if(confirm('Remove this package and its item configuration when you save?')) {plan.packages=plan.packages.filter(p=>p.key!==target.dataset.key);saveDraft();render();}
            }
            else if (action === 'bulk' || action === 'single') {
                if (!plan.packages.length) throw new Error('Configure a package first.');
                open(action==='bulk'?'Bulk add School IDs':'Add school', `${selection()}${action==='single'?`<div class="flex gap-2"><input data-school-search placeholder="School ID or name" class="min-w-0 flex-1 rounded-lg border-slate-200 text-sm">${button('search','Search schools')}</div><div data-school-results class="space-y-2"></div>`:''}<label class="block text-xs font-semibold">School IDs (one per line)<textarea data-school-ids rows="6" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></textarea></label>${button('preview-ids','Validate & preview')}`);
            }
            else if(action==='search') {
                const url=new URL(root.dataset.searchUrl,location.href);url.searchParams.set('q',content.querySelector('[data-school-search]').value);
                const response=await fetch(url,{headers:{Accept:'application/json'}});if(!response.ok)throw new Error('School search failed.');
                const json=await response.json();content.querySelector('[data-school-results]').innerHTML=json.data.map(s=>`<div class="rounded-lg border p-3 text-xs"><strong>${escape(s.school_id)} — ${escape(s.school_name)}</strong><p>${escape([s.region,s.division,s.municipality,s.address].join(' / '))}</p>${button('select-school','Select school',`data-school="${escape(s.school_id)}"`)}</div>`).join('') || '<p>No matching schools.</p>';
            }
            else if(action==='select-school')content.querySelector('[data-school-ids]').value=target.dataset.school;
            else if(action==='preview-ids')await preview({ids:content.querySelector('[data-school-ids]').value,package_key:content.querySelector('[data-package-select]').value,mode:'add'});
            else if(action==='list')await listing(1,target.dataset.key);
            else if(action==='page' || action==='filter')await listing(Number(target.dataset.page || 1),content.querySelector('[data-package-select]').value,content.querySelector('[data-assignment-search]').value);
            else if(action==='remove-assignment' && confirm('Remove this assignment when you save?')) {token=(await api('remove',{school_id:target.dataset.school,package_key:target.dataset.key})).token;saveDraft();await listing();}
            else if(action==='change-assignment') {reassignment={school_id:target.dataset.school,old_package_key:target.dataset.key};open('Change school assignment',`${selection()}${button('save-assignment','Apply reassignment')}`);}
            else if(action==='save-assignment') {token=(await api('reassign',{...reassignment,package_key:content.querySelector('[data-package-select]').value,school_plan:JSON.stringify(plan)})).token;saveDraft();await listing();}
            else if(action==='import') {
                if (!plan.packages.length) throw new Error('Configure lot packages before importing.');
                open('Import project masterlist', `<p class="text-sm text-slate-600">Upload one CSV or XLSX sheet (up to 10 MB). Map columns for every lot in the file.</p><input data-masterlist type="file" accept=".csv,.xlsx" class="text-sm">${button('upload','Upload & map columns')}`);
            }
            else if(action==='upload') {
                const file=content.querySelector('[data-masterlist]').files[0];if(!file)throw new Error('Choose a masterlist file.');
                const result=await api('upload',{},file);token=result.token;
                const select=(name,guess)=>`<select data-mapping="${escape(name)}" class="w-full rounded-lg border-slate-200 text-sm"><option value="">Select column</option>${result.headers.map(h=>`<option ${h.toLowerCase().replace(/\W/g,'')===guess?'selected':''}>${escape(h)}</option>`).join('')}</select>`;
                const lots=[...new Set(plan.packages.map(p=>p.lot_no))];
                open('Map masterlist columns', `<p class="text-xs">${result.rows} rows detected</p><div class="grid gap-3 sm:grid-cols-2"><label class="text-xs font-semibold">School ID${select('school','schoolid')}</label><label class="text-xs font-semibold">Grade / key stage${select('grade','gradelevel')}</label>${lots.map(l=>`<label class="text-xs font-semibold">${escape(l)} package column${select(l,l.toLowerCase().replace(/\W/g,''))}</label>`).join('')}</div><label class="block text-xs font-semibold">Import mode<select data-import-mode class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option value="add">Add assignments; retain existing assignments</option><option value="replace">Replace all school assignments in this project (preview removals)</option></select></label>${button('preview-file','Validate & preview')}`);
            }
            else if(action==='preview-file') {
                const mapping={lots:{}};for(const el of content.querySelectorAll('[data-mapping]')){if(['school','grade'].includes(el.dataset.mapping))mapping[el.dataset.mapping]=el.value;else if(el.value)mapping.lots[el.value]=el.dataset.mapping;}
                await preview({mapping,mode:content.querySelector('[data-import-mode]').value});
            }
            else if(action==='confirm') {const result=await api('confirm',{token:pendingToken});token=result.token;saveDraft();await listing();message.textContent='Assignments confirmed. Save the bidding document to apply them.';}
            else if(action==='errors') {const blob=await api('errors',{token:pendingToken},null,true);const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download='masterlist-errors.csv';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);}
        } catch(e) {(dialog.open?error:message).textContent=e.message;}
        finally {busy=false;target.disabled=false;}
    });
    root.addEventListener('change',event=>{
        if(event.target.matches('select[data-edit="catalog_item_id"]')){
            const json=event.target.selectedOptions[0]?.dataset.item;if(!json)return;
            const item=JSON.parse(json), row=event.target.closest('[data-package-item]');
            for(const [key,value] of Object.entries({item_description:item.description || item.item_name,unit:item.unit,unit_cost:item.price}))row.querySelector(`[data-edit="${key}"]`).value=value || '';
        }
    });
    form?.addEventListener('submit',event=>{if(busy || dialog.open){event.preventDefault();event.stopImmediatePropagation();message.textContent='Finish or close the school assignment dialog before saving.';}},true);
    form?.addEventListener('change',event=>{if(event.target.matches('[data-lot-number]'))render();});
    if(token)saveDraft();
    render();
}
for (const root of document.querySelectorAll('[data-school-workspace]')) initializeSchools(root);
