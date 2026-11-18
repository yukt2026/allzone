@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Warehouse & Fulfilment Management - ALLZONE'])

@section('content')
<div 
    x-data="{
        addModalOpen: false,
        editModalOpen: false,
        editingLocation: null,
        loadingEdit: false,
        async openEditModal(id) {
            this.editModalOpen = true;
            this.loadingEdit = true;
            try {
                const res = await fetch(`{{ url('/seller/warehouse') }}/${id}`);
                if (!res.ok) throw new Error('Failed to load location details.');
                this.editingLocation = await res.json();
            } catch (e) {
                alert(e.message || 'Error loading location details.');
                this.editModalOpen = false;
            } finally {
                this.loadingEdit = false;
            }
        }
    }" 
    class="space-y-6 max-w-7xl mx-auto pb-16"
>
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Warehouse &amp; Fulfilment Management
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Configure seller dispatch pickup addresses, Shiprocket logistics integration, and standard shipment packaging.
            </p>
        </div>

        <button 
            type="button" 
            @click="addModalOpen = true" 
            class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center space-x-1.5 shadow-xs transition"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add Pickup Location</span>
        </button>
    </div>

    <!-- Alert / Feedback Messages -->
    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold space-y-1 shadow-2xs">
            @foreach($errors->all() as $err)
                <div class="flex items-center space-x-1.5">
                    <span>•</span>
                    <span>{{ $err }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Top Metrics Overview Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- Metric 1: Total Locations -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pickup Locations</span>
            <div class="text-2xl font-black text-slate-900 font-mono mt-1">{{ $metrics['total_locations'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Authorized seller hubs</div>
        </div>

        <!-- Metric 2: Primary Dispatch Hub -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Primary Dispatch Hub</span>
            <div class="text-sm font-bold text-slate-900 truncate mt-1">
                {{ $defaultLocation?->nickname ?? 'None Configured' }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                PIN: <span class="font-mono font-bold text-slate-800">{{ $defaultLocation?->pincode ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Metric 3: Verification Status -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Verified Locations</span>
            <div class="text-2xl font-black text-emerald-700 font-mono mt-1">{{ $metrics['verified_locations'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">
                {{ $metrics['pending_verification'] }} pending audit
            </div>
        </div>

        <!-- Metric 4: Shiprocket Serviceability -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Shiprocket Serviceability</span>
            <div class="text-2xl font-black text-slate-900 font-mono mt-1">{{ $metrics['serviceable_locations'] }}</div>
            <div class="text-[11px] text-emerald-600 font-medium mt-1">✓ Automated courier pickup</div>
        </div>
    </div>

    <!-- Primary Dispatch Hub Featured Card -->
    @if($defaultLocation)
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-5">
            <div class="space-y-2">
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px] border border-emerald-200">
                        Primary Dispatch Hub
                    </span>
                    @if($defaultLocation->is_verified)
                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px] border border-emerald-200 flex items-center space-x-1">
                            <span>✓</span>
                            <span>Verified by Admin</span>
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-bold text-[10px] border border-amber-200">
                            Verification Pending
                        </span>
                    @endif
                </div>

                <div class="text-base font-black text-slate-900">
                    {{ $defaultLocation->nickname }}
                    @if($defaultLocation->shiprocket_pickup_name)
                        <span class="text-xs font-mono font-normal text-slate-400 ml-1.5">(Shiprocket Tag: {{ $defaultLocation->shiprocket_pickup_name }})</span>
                    @endif
                </div>

                <div class="text-xs text-slate-600 max-w-2xl leading-relaxed">
                    {{ $defaultLocation->full_address }}
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 pt-1">
                    <span>Contact: <strong class="text-slate-800">{{ $defaultLocation->contact_person }}</strong></span>
                    <span>Phone: <strong class="text-slate-800 font-mono">{{ $defaultLocation->contact_phone }}</strong></span>
                    @if($defaultLocation->gstin)
                        <span>GSTIN: <strong class="text-slate-800 font-mono">{{ $defaultLocation->gstin }}</strong></span>
                    @endif
                    <span>Hours: <strong class="text-slate-800">{{ $defaultLocation->operating_hours ?? '09:00 AM - 06:00 PM' }}</strong></span>
                </div>
            </div>

            <div class="border-t md:border-t-0 md:border-l border-slate-100 pt-3 md:pt-0 md:pl-5 text-xs shrink-0 flex flex-col space-y-2">
                <div class="text-[11px] text-slate-500">
                    <div>Courier Network:</div>
                    @if($defaultLocation->is_serviceable)
                        <span class="font-bold text-emerald-700 font-mono">
                            {{ count($defaultLocation->serviceability_details['available_couriers'] ?? []) }} Couriers Available
                        </span>
                    @else
                        <span class="font-bold text-rose-600">PIN not automated</span>
                    @endif
                </div>

                <form method="POST" action="{{ route('seller.warehouse.check_serviceability', $defaultLocation) }}">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-[11px] font-bold shadow-2xs transition">
                        ↻ Re-check Serviceability
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- All Registered Pickup Locations Grid -->
    <div class="space-y-4">
        <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">
            Registered Pickup Locations ({{ $locations->count() }})
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($locations as $loc)
                <div class="p-4 rounded-xl bg-white border {{ $loc->is_default ? 'border-emerald-300 ring-1 ring-emerald-400/30' : 'border-slate-200' }} shadow-2xs flex flex-col justify-between space-y-3">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <h3 class="font-bold text-slate-900 text-sm">{{ $loc->nickname }}</h3>
                                @if($loc->is_default)
                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-black">DEFAULT</span>
                                @endif
                            </div>

                            @if($loc->is_verified)
                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                                    Verified
                                </span>
                            @elseif($loc->status === 'rejected')
                                <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 text-[10px] font-bold border border-rose-200">
                                    Rejected
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 text-[10px] font-bold border border-amber-200">
                                    Pending Audit
                                </span>
                            @endif
                        </div>

                        <p class="text-xs text-slate-600 line-clamp-2">
                            {{ $loc->full_address }}
                        </p>

                        <div class="text-[11px] text-slate-500 space-y-0.5">
                            <div>Contact: <span class="text-slate-800 font-medium">{{ $loc->contact_person }}</span> ({{ $loc->contact_phone }})</div>
                            @if($loc->gstin)
                                <div>GSTIN: <span class="font-mono text-slate-700 font-medium">{{ $loc->gstin }}</span></div>
                            @endif
                            @if($loc->shiprocket_pickup_name)
                                <div>Shiprocket Code: <span class="font-mono text-slate-700 font-medium">{{ $loc->shiprocket_pickup_name }}</span></div>
                            @endif
                        </div>

                        @if($loc->rejection_reason)
                            <div class="p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-[11px]">
                                <strong>Rejection Reason:</strong> {{ $loc->rejection_reason }}
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-2">
                            @if(!$loc->is_default)
                                <form method="POST" action="{{ route('seller.warehouse.set_default', $loc) }}">
                                    @csrf
                                    <button type="submit" class="text-emerald-600 hover:text-emerald-800 font-bold">
                                        Make Primary Hub
                                    </button>
                                </form>
                                <span class="text-slate-300">&bull;</span>
                            @endif
                            <button type="button" @click="openEditModal({{ $loc->id }})" class="text-slate-600 hover:text-slate-900 font-bold">
                                Edit
                            </button>
                        </div>

                        @if(!$loc->is_default)
                            <form method="POST" action="{{ route('seller.warehouse.destroy', $loc) }}" onsubmit="return confirm('Remove this pickup address?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold text-[11px]">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-2 p-8 text-center bg-white rounded-xl border border-slate-200">
                    <span class="text-3xl text-slate-300 mb-2 block">📍</span>
                    <div class="font-bold text-slate-800 text-sm">No pickup locations configured</div>
                    <div class="text-xs text-slate-400 mt-1">Add your warehouse or shop pickup address so carriers can pick up orders.</div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Standard Shipment Package Configuration -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-5 space-y-4">
        <div>
            <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">
                Standard Shipment Packaging Presets
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Save your standard parcel dimensions (L x W x H in cm) and tare weight (in grams). Used for rapid dispatch rate validation with Shiprocket.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($defaultPackageConfig as $pkgKey => $pkg)
                <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200/80 text-xs space-y-2">
                    <div class="font-bold text-slate-900 text-xs flex items-center justify-between">
                        <span>{{ $pkg['name'] }}</span>
                        <span class="font-mono text-[10px] text-slate-400">#{{ $pkgKey }}</span>
                    </div>
                    <div class="text-slate-600 space-y-0.5 text-[11px]">
                        <div>Dimensions: <strong class="text-slate-900 font-mono">{{ $pkg['length'] }} &times; {{ $pkg['width'] }} &times; {{ $pkg['height'] }} cm</strong></div>
                        <div>Dead Weight: <strong class="text-slate-900 font-mono">{{ $pkg['weight_grams'] }} grams ({{ $pkg['weight_grams'] / 1000 }} kg)</strong></div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Add Custom Box Preset Form -->
        <div class="pt-3 border-t border-slate-100">
            <h3 class="text-xs font-bold text-slate-800 mb-2">Save Custom Parcel Preset</h3>
            <form method="POST" action="{{ route('seller.warehouse.package_config') }}" class="grid grid-cols-2 sm:grid-cols-6 gap-2 text-xs">
                @csrf
                <div class="col-span-2">
                    <input type="text" name="box_name" placeholder="Preset Name (e.g. Earphone Box)" required class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs">
                </div>
                <div>
                    <input type="number" name="length" placeholder="Len (cm)" min="1" max="200" required class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs">
                </div>
                <div>
                    <input type="number" name="width" placeholder="Wid (cm)" min="1" max="200" required class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs">
                </div>
                <div>
                    <input type="number" name="height" placeholder="Hgt (cm)" min="1" max="200" required class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs">
                </div>
                <div class="flex items-center space-x-1.5">
                    <input type="number" name="weight_grams" placeholder="Grams" min="50" max="50000" required class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs">
                    <button type="submit" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-bold text-xs shrink-0">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- ADD PICKUP LOCATION MODAL                                                 -->
    <!-- ========================================================================= -->
    <div x-show="addModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div @click.away="addModalOpen = false" class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden text-xs">
            <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
                <h3 class="font-bold text-sm text-slate-100">Add Seller Pickup Address</h3>
                <button type="button" @click="addModalOpen = false" class="text-slate-400 hover:text-white font-bold text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('seller.warehouse.store') }}" class="p-5 space-y-3.5 max-h-[80vh] overflow-y-auto">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Location Nickname *</label>
                    <input type="text" name="nickname" placeholder="e.g. Primary Electronics Hub, Delhi Depot" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contact Person *</label>
                        <input type="text" name="contact_person" placeholder="Warehouse Manager Name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contact Phone *</label>
                        <input type="text" name="contact_phone" placeholder="10-digit mobile number" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Contact Email</label>
                    <input type="email" name="contact_email" placeholder="dispatch@yourbusiness.com" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Address Line 1 (Building / Street) *</label>
                    <input type="text" name="address_line1" placeholder="Plot No, Street, Industrial Area" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Address Line 2 (Landmark / Floor)</label>
                    <input type="text" name="address_line2" placeholder="Near Metro Station / Landmark" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">City *</label>
                        <input type="text" name="city" placeholder="Bengaluru" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">State *</label>
                        <input type="text" name="state" placeholder="Karnataka" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pincode *</label>
                        <input type="text" name="pincode" placeholder="6-digit PIN" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">GSTIN (Optional APOB)</label>
                        <input type="text" name="gstin" placeholder="15-digit GSTIN" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono uppercase">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Operating Hours</label>
                        <input type="text" name="operating_hours" placeholder="e.g. 09:00 AM - 06:00 PM" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="inline-flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="text-xs font-bold text-slate-800">Set as primary dispatch hub for all Shiprocket pickups</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-200 flex items-center justify-end space-x-2">
                    <button type="button" @click="addModalOpen = false" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 font-bold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold">Save &amp; Verify Serviceability</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- EDIT PICKUP LOCATION MODAL                                                -->
    <!-- ========================================================================= -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div @click.away="editModalOpen = false" class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden text-xs">
            <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
                <h3 class="font-bold text-sm text-slate-100">Edit Pickup Address</h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-white font-bold text-lg">&times;</button>
            </div>

            <div x-show="loadingEdit" class="py-12 text-center text-slate-500">
                <span class="animate-spin inline-block text-2xl mb-2">↻</span>
                <div class="font-semibold text-xs">Loading address details...</div>
            </div>

            <template x-if="editingLocation && !loadingEdit">
                <form :action="`{{ url('/seller/warehouse') }}/${editingLocation.id}`" method="POST" class="p-5 space-y-3.5 max-h-[80vh] overflow-y-auto">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Location Nickname *</label>
                        <input type="text" name="nickname" x-model="editingLocation.nickname" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Contact Person *</label>
                            <input type="text" name="contact_person" x-model="editingLocation.contact_person" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Contact Phone *</label>
                            <input type="text" name="contact_phone" x-model="editingLocation.contact_phone" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contact Email</label>
                        <input type="email" name="contact_email" x-model="editingLocation.contact_email" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Address Line 1 *</label>
                        <input type="text" name="address_line1" x-model="editingLocation.address_line1" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Address Line 2</label>
                        <input type="text" name="address_line2" x-model="editingLocation.address_line2" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">City *</label>
                            <input type="text" name="city" x-model="editingLocation.city" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">State *</label>
                            <input type="text" name="state" x-model="editingLocation.state" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pincode *</label>
                            <input type="text" name="pincode" x-model="editingLocation.pincode" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">GSTIN</label>
                            <input type="text" name="gstin" x-model="editingLocation.gstin" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono uppercase">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Operating Hours</label>
                            <input type="text" name="operating_hours" x-model="editingLocation.operating_hours" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="is_default" value="1" :checked="editingLocation.is_default" class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-slate-800">Set as primary dispatch hub</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-200 flex items-center justify-end space-x-2">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 font-bold hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold">Update Location</button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection
