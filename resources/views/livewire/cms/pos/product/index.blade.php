<div>
    <h1 class="h3 mb-3">
        {{ $title ?? '' }}
    </h1>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title">{{ $title ?? '' }} Data</h5>
        </div>
        <div class="card-body">
            <x-acc-header :$originRoute />
            <div class="table-responsive">
                <x-acc-table>
                    <thead>
                        <tr>
                            <x-acc-loop-th :$searchBy :$orderBy :$order />
                            <th>
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($get as $d)
                            <tr>
                                <td>{{ $d->category_name }}</td>
                                <td>{{ $d->sub_category_name ?? '-' }}</td>
                                <td>{{ $d->merk_name ?? '-' }}</td>
                                <td>{{ $d->sku }}</td>
                                <td>{{ $d->name }}</td>
                                <td>
                                    {{  number_format($d->price, 0, ',', '.') }}
                                </td>
                                <td>
                                    {{ number_format($d->stock, 0, ',', '.') }}
                                </td>
                                <td>{{ $d->description }}</td>
                                <td>
                                    <span class="{{ $d->status->color() }}">
                                        {{ $d->status->label() }}
                                    </span>
                                </td>
                                <x-acc-update-delete :id="$d->id" :$originRoute editClick="customEdit">
                                    <a class="dropdown-item" href="{{ route('cms.product.variant', [
                                        'productId' => $d->id,
                                    ]) }}" wire:navigate>
                                        <i class="fa fa-cube"></i>
                                        <span class="ms-2">
                                            Variant
                                        </span>
                                    </a>
                                </x-acc-update-delete>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100" class="text-center">
                                    No Data Found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-acc-table>
            </div>
            <div class="float-end">
                {{ $get->links() }}
            </div>
        </div>
    </div>

    {{-- Create / Update Modal --}}
    <x-acc-modal title="{{ $isUpdate ? 'Update' : 'Create' }} {{ $title }}" size="lg" modal="acc-modal">
        <x-acc-form submit="save">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Category <x-acc-required /></label>
                    <x-acc-input type="select" model="form.product_category_id" :live="true" wire:change="getSubCategories" icon="fa fa-cube">
                        <option value="">--Select Category--</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-acc-input>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Sub Category </label>
                    <x-acc-input type="select" model="form.product_sub_category_id" id="product_sub_category_id" icon="fa fa-cube">
                        <option value="">--Select Sub Category--</option>
                        @foreach($subCategories as $subCategory)
                            <option value="{{ $subCategory->id }}">{{ $subCategory->name }}</option>
                        @endforeach
                    </x-acc-input>
                </div>
            </div>
            <div class="col-md-12">
                <div class="mb-3">
                    <label class="form-label">Merk</label>
                    <x-acc-input type="select" model="form.product_merk_id" icon="fa fa-cube">
                        <option value="">--Select Merk--</option>
                        @foreach($merks as $merk)
                            <option value="{{ $merk->id }}">{{ $merk->name }}</option>
                        @endforeach
                    </x-acc-input>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Sku <x-acc-required /></label>
                    <x-acc-input model="form.sku" icon="fa fa-code" />
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Name <x-acc-required /></label>
                    <x-acc-input model="form.name" icon="fa fa-cube" />
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Price <x-acc-required /></label>
                    <x-acc-input model="form.price" icon="fa fa-money" x-mask:dynamic="$money($input, ',')" />
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Stock <x-acc-required /></label>
                    <x-acc-input model="form.stock" icon="fa fa-money" x-mask:dynamic="$money($input, ',')" />
                </div>
            </div>
            <div class="col-md-12">
                <div class="mb-3">
                    <label class="form-label">Description <x-acc-required /></label>
                    <x-acc-input type="textarea" model="form.description" placeholder="Description" icon="fa fa-list" />
                </div>
            </div>
            <div class="col-md-12">
                <div class="mb-3">
                    <label class="form-label">Image</label>
                    <x-acc-image-preview :image="$form->image" :form_image="$form->old_data?->getFirstMediaUrl('images')" />
                    <x-acc-input-file model="form.image" accept="image/*" :$imageIttr />
                </div>
            </div>
            <div class="col-md-12">
                <div class="mb-3">
                    <label class="form-label">Status <x-acc-required /></label>
                    <x-acc-input type="select" model="form.status" icon="fa fa-lock">
                        <option value="">-- Select Status --</option>
                        @foreach(App\Enums\CommonStatusEnum::cases() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </x-acc-input>
                </div>
            </div>
        </x-acc-form>
    </x-acc-modal>
</div>
