/**
 * Visual workflow designer (PyVelm pvWorkflowBuilder parity).
 */
(() => {
    let _uid = 0;
    const nextUid = () => 'w' + (++_uid);

    function factory(cfg) {
        const defn = cfg.definition || { version: 1, model: '', states: [], transitions: [] };
        (defn.states || []).forEach((s) => { if (!s._uid) s._uid = nextUid(); });
        (defn.transitions || []).forEach((t) => {
            if (!t._uid) t._uid = nextUid();
            t.fromSingle = (t.from && t.from[0]) || '';
            if (!t.form) t.form = { title: t.label || '', fields: [] };
            (t.form.fields || []).forEach((f) => { if (!f._uid) f._uid = nextUid(); });
            if (!t.approval) t.approval = { strategy: 'any', assignee_type: 'group', group_id: null };
        });

        return {
            workflowId: cfg.workflowId,
            step: 1,
            steps: [
                { id: 1, label: 'Basics' },
                { id: 2, label: 'States' },
                { id: 3, label: 'Transitions' },
                { id: 4, label: 'Review' },
            ],
            meta: cfg.meta || { name: '', description: '', model: '', active: true },
            definition: defn,
            models: cfg.models || [],
            groups: cfg.groups || [],
            users: cfg.users || [],
            recordFields: cfg.recordFields || [],
            saving: false,
            saveMessage: '',
            saveError: '',

            goStep(id) {
                this.step = id;
                if (id === 3) this.loadRecordFields();
            },

            onModelChange() {
                this.definition.model = this.meta.model;
                this.loadRecordFields();
            },

            async loadRecordFields() {
                if (!this.meta.model) return;
                const r = await fetch('/web/workflow/api/fields?model=' + encodeURIComponent(this.meta.model), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (r.ok) this.recordFields = await r.json();
            },

            userFields() {
                return (this.recordFields || []).filter((rf) =>
                    rf.type === 'many2one' && (rf.comodel === 'res.users' || (rf.name || '').endsWith('_id')),
                );
            },

            addState() {
                const key = 'state_' + (this.definition.states.length + 1);
                this.definition.states.push({
                    _uid: nextUid(), key, label: key.replace(/_/g, ' '), initial: false, final: false,
                });
            },

            removeState(i) { this.definition.states.splice(i, 1); },

            setInitial(uid) {
                this.definition.states.forEach((s) => { s.initial = s._uid === uid; });
            },

            addTransition() {
                const from = this.definition.states[0]?.key || 'draft';
                const to = this.definition.states[1]?.key || from;
                this.definition.transitions.push({
                    _uid: nextUid(),
                    key: 'action_' + (this.definition.transitions.length + 1),
                    label: 'New action',
                    fromSingle: from,
                    to,
                    kind: 'user',
                    approval: { strategy: 'any', assignee_type: 'group', group_id: null },
                    reject_to: from,
                    form: { title: 'Details', fields: [] },
                });
            },

            removeTransition(i) { this.definition.transitions.splice(i, 1); },

            addFormField(tr) {
                if (!tr.form) tr.form = { title: tr.label, fields: [] };
                tr.form.fields.push({
                    _uid: nextUid(), name: 'note', label: 'Note', type: 'text', source: 'stage', required: false,
                });
            },

            onFieldSourceChange(ff) {
                if (ff.source === 'record' && this.recordFields.length) {
                    ff.name = this.recordFields[0].name;
                }
            },

            buildDefinition() {
                const states = this.definition.states.map((s) => {
                    const o = { key: s.key, label: s.label || s.key };
                    if (s.initial) o.initial = true;
                    if (s.final) o.final = true;
                    if (s.cancelled) o.cancelled = true;
                    return o;
                });
                const transitions = this.definition.transitions.map((t) => {
                    const o = {
                        key: t.key,
                        label: t.label || t.key,
                        from: [t.fromSingle || (t.from && t.from[0]) || 'draft'],
                        to: t.to,
                        kind: t.kind || 'user',
                    };
                    if (t.kind === 'approval') {
                        const ac = t.approval || {};
                        o.approval = {
                            strategy: ac.strategy || 'any',
                            assignee_type: ac.assignee_type || 'group',
                        };
                        if (ac.group_id) o.approval.group_id = ac.group_id;
                        if (ac.user_id) o.approval.user_id = ac.user_id;
                        if (ac.user_field) o.approval.user_field = ac.user_field;
                        if (ac.deadline_hours) o.approval.deadline_hours = ac.deadline_hours;
                        if (ac.escalate_to_group_id) o.approval.escalate_to_group_id = ac.escalate_to_group_id;
                        if (t.reject_to) o.reject_to = t.reject_to;
                    }
                    const fields = (t.form?.fields || []).map((f) => {
                        const ff = { name: f.name, label: f.label || f.name, source: f.source || 'stage' };
                        if (ff.source === 'stage') ff.type = f.type || 'char';
                        if (f.required) ff.required = true;
                        return ff;
                    });
                    if (fields.length) o.form = { title: t.form?.title || t.label, fields };
                    return o;
                });
                const out = { version: 1, model: this.meta.model, states, transitions };
                if (this.definition.auto_start) out.auto_start = true;
                return out;
            },

            buildPayload() {
                return {
                    name: this.meta.name,
                    description: this.meta.description,
                    model: this.meta.model,
                    active: this.meta.active,
                    definition: this.buildDefinition(),
                };
            },

            async save() {
                this.saving = true;
                this.saveMessage = '';
                this.saveError = '';
                try {
                    const url = this.workflowId
                        ? '/web/workflow/api/definitions/' + this.workflowId
                        : '/web/workflow/api/definitions';
                    const r = await fetch(url, {
                        method: this.workflowId ? 'PUT' : 'POST',
                        headers: this.jsonHeaders(),
                        body: JSON.stringify(this.buildPayload()),
                    });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data.message || 'Save failed');
                    if (!this.workflowId && data.id) {
                        window.location.href = '/web/workflow/' + data.id + '/build';
                        return;
                    }
                    this.saveMessage = 'Saved.';
                } catch (e) {
                    this.saveError = e.message || String(e);
                } finally {
                    this.saving = false;
                }
            },

            jsonHeaders() {
                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                return {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                };
            },
        };
    }

    const register = () => Alpine.data('pvWorkflowBuilder', factory);
    if (typeof Alpine !== 'undefined') register();
    else document.addEventListener('alpine:init', register);
})();
