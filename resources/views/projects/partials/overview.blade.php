<div id="overview" class="tab-content space-y-6">

                <div class="grid lg:grid-cols-2 gap-6">

                    {{-- PROJECT INFORMATION --}}
                    <div class="border border-slate-200 rounded-2xl p-5">

                        <h2 class="text-sm font-black text-slate-900 mb-5">
                            Project Information
                        </h2>

                        <div class="grid sm:grid-cols-2 gap-5">

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Reference No.
                                </p>

                                <p class="font-mono font-bold mt-1">
                                    {{ $project->ref_no ?: 'Not Set' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Project Code
                                </p>

                                <p class="font-mono font-bold mt-1">
                                    {{ $project->project_code ?: 'Not Set' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Agency
                                </p>

                                <p class="font-semibold mt-1">
                                    {{ $project->agency ?: 'Not Set' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Key Stage
                                </p>

                                <p class="font-semibold mt-1">
                                    {{ $project->keystage == 1 ? 'Enabled' : 'Not enabled' }}
                                </p>
                            </div>

                        </div>

                    </div>


                    {{-- FINANCIAL INFORMATION --}}
                    <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200">

                        <h2 class="text-sm font-black text-slate-900 mb-5">
                            Financial Information
                        </h2>

                        <div class="space-y-4">

                            <div>
                                <p class="text-xs text-slate-400 uppercase">
                                    Contract Amount
                                </p>

                                <p class="text-2xl font-black text-slate-900">
                                    &#8369;{{ number_format($project->contract_amount ?? 0, 2) }}
                                </p>
                            </div>

                            <div class="border-t border-slate-200 pt-3">

                                <p class="text-xs text-slate-400 uppercase">
                                    ABC
                                </p>

                                <p class="font-bold text-lg">
                                    &#8369;{{ number_format($project->ABC ?? 0, 2) }}
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- PROJECT STRUCTURE --}}
                <div>

                    <h2 class="text-sm font-black text-slate-900 mb-4">
                        Project Structure
                    </h2>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Schools
                            </p>

                            <p class="text-xl font-black mt-1">
                                <span data-project-count="schools">{{ $schoolCount }}</span>
                            </p>
                        </div>

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Lots
                            </p>

                            <p class="text-xl font-black mt-1">
                                <span data-project-count="lots">{{ $lotCount }}</span>
                            </p>
                        </div>

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Keystages
                            </p>

                            <p class="text-xl font-black mt-1">
                                <span data-project-count="keystage">{{ $keystageCount }}</span>
                            </p>
                        </div>

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Items
                            </p>

                            <p class="text-xl font-black mt-1">
                                <span data-project-count="items">{{ $itemCount }}</span>
                            </p>
                        </div>

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-slate-400 uppercase">
                                Packages
                            </p>

                            <p class="text-xl font-black mt-1">
                                <span data-project-count="packages">{{ $packageCount }}</span>
                            </p>
                        </div>

                    </div>

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- SCHOOLS --}}
            {{-- ========================================================= --}}
            