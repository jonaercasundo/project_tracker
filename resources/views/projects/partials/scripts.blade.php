{{-- PROJECT LIST PAGINATION --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (! @json($structureIsSmall)) { return; }
        const createPaginator = ({ rowSelector, resultCountId, buttonsId, label }) => {
            const rows = Array.from(document.querySelectorAll(rowSelector));
            const resultCount = document.getElementById(resultCountId);
            const buttons = document.getElementById(buttonsId);
            const perPage = 10;
            let currentPage = 1;

            if (!buttons) {
                return;
            }

            const render = () => {
                const visibleRows = rows.filter((row) => !row.classList.contains('hidden'));
                const totalPages = Math.max(1, Math.ceil(visibleRows.length / perPage));

                currentPage = Math.min(currentPage, totalPages);

                const start = (currentPage - 1) * perPage;
                const end = Math.min(start + perPage, visibleRows.length);

                rows.forEach((row) => {
                    row.style.display = 'none';
                });

                visibleRows.slice(start, end).forEach((row) => {
                    row.style.display = '';
                });

                if (resultCount) {
                    resultCount.innerHTML = visibleRows.length
                        ? `Showing <span class="font-bold text-slate-600">${start + 1}-${end}</span> of <span class="font-bold text-slate-600">${visibleRows.length}</span> ${label}`
                        : `No ${label} found`;
                }

                buttons.innerHTML = '';

                if (totalPages <= 1) {
                    return;
                }

                const addButton = (text, page, disabled = false, active = false) => {
                    const button = document.createElement('button');

                    button.type = 'button';
                    button.textContent = text;
                    button.disabled = disabled;
                    button.className = active
                        ? 'inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-blue-600 px-2 text-xs font-bold text-white'
                        : 'inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 bg-white px-2 text-xs font-bold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40';

                    button.addEventListener('click', () => {
                        currentPage = page;
                        render();
                    });

                    buttons.appendChild(button);
                };

                addButton('Prev', currentPage - 1, currentPage === 1);

                for (let page = 1; page <= totalPages; page++) {
                    if (page === 1 || page === totalPages || Math.abs(page - currentPage) <= 1) {
                        addButton(page, page, false, page === currentPage);
                    }
                }

                addButton('Next', currentPage + 1, currentPage === totalPages);
            };

            const tableBody = rows[0]?.parentElement;

            if (tableBody) {
                new MutationObserver(render).observe(tableBody, {
                    attributes: true,
                    attributeFilter: ['class'],
                    subtree: true,
                });
            }

            render();
        };



        createPaginator({
            rowSelector: '.lot-row',
            resultCountId: 'lotResultCount',
            buttonsId: 'lotsPaginationButtons',
            label: 'lots',
        });

        createPaginator({
            rowSelector: '.keystage-row',
            resultCountId: 'keystageResultCount',
            buttonsId: 'keystagesPaginationButtons',
            label: 'keystages',
        });
    });
</script>

{{-- ============================================================= --}}
{{-- TAB SCRIPT --}}
{{-- ============================================================= --}}

<script>
function openTab(event, tabName) {

    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.add('hidden');
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove(
            'border-blue-600',
            'text-blue-600',
            'bg-white'
        );

        btn.classList.add(
            'border-transparent',
            'text-slate-500'
        );
    });

    const content = document.getElementById(tabName);

    if (content) {
        content.classList.remove('hidden');
    }

    window.loadProjectDetails?.(tabName);
    const btn = document.querySelector(`[data-tab="${tabName}"]`) || event.currentTarget;

    btn.classList.remove(
        'border-transparent',
        'text-slate-500'
    );

    btn.classList.add(
        'border-blue-600',
        'text-blue-600',
        'bg-white'
    );

    localStorage.setItem(
        'activeProjectTab',
        tabName
    );
}


document.addEventListener('DOMContentLoaded', () => {

    const saved = localStorage.getItem('activeProjectTab');

    if (saved) {

        const btn = document.querySelector(
            `[data-tab="${saved}"]`
        );

        if (btn) {
            btn.click();
        }

    }

});
</script>

