<div>
    <!-- Search Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true"
        x-data="{
            search: '',
            links: {{ Js::from($this->quickLinks) }},
            init() {
                const modal = document.getElementById('exampleModal');
                modal.addEventListener('shown.bs.modal', () => {
                    setTimeout(() => this.$refs.searchInput.focus(), 150);
                });
            },
            get filteredLinks() {
                const q = this.search.toLowerCase().trim();
                if (!q) return this.links;
                return this.links.reduce((acc, group) => {
                    const items = group.items.filter(item =>
                        item.label.toLowerCase().includes(q)
                    );
                    if (items.length) acc.push({ ...group, items });
                    return acc;
                }, []);
            }
        }"
        x-on:hidden.bs.modal.window="search = ''">
        <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <input type="search" class="form-control fs-3" placeholder="Cari halaman..."
                        x-model="search" x-ref="searchInput" autofocus />
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="lh-1">
                        <i class="ti ti-x fs-5 ms-3"></i>
                    </a>
                </div>
                <div class="modal-body message-body" data-simplebar>
                    <h5 class="mb-0 fs-5 p-1">Quick Page Links</h5>
                    <ul class="list-unstyled p-2" id="search-results" style="list-style: none; padding-left: 0;">
                        <template x-for="group in filteredLinks" :key="group.category">
                            <div>
                                <li class="p-2 mt-2" x-show="!search">
                                    <span class="text-uppercase fw-bold text-primary fs-2" x-text="group.category"></span>
                                </li>
                                <template x-for="item in group.items" :key="item.label">
                                    <li class="p-1 mb-1 bg-hover-light-black rounded">
                                        <a :href="item.url"
                                            class="d-flex align-items-center gap-2 text-decoration-none">
                                            <i :class="item.icon + ' fs-5 text-muted flex-shrink-0'" style="width: 24px;"></i>
                                            <div class="flex-grow-1 min-width-0">
                                                <span class="d-block text-dark" x-text="item.label"></span>
                                                <span class="text-muted d-block fs-2"
                                                    x-show="item.path"
                                                    x-text="item.path"></span>
                                            </div>
                                            <i class="ti ti-lock text-warning flex-shrink-0" style="font-size: 0.7rem;"
                                                title="Fitur premium — upgrade untuk mengakses"
                                                x-show="item.locked"></i>
                                            <i class="ti ti-arrow-narrow-right text-muted fs-5 flex-shrink-0"
                                                x-show="!item.locked"></i>
                                        </a>
                                    </li>
                                </template>
                            </div>
                        </template>
                        <li class="p-4 text-center text-muted" x-show="filteredLinks.length === 0">
                            <i class="ti ti-search-off fs-8 d-block mb-2"></i>
                            <span>Tidak ada halaman yang cocok dengan "<strong x-text="search"></strong>"</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
