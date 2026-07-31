<x-mi_app>

<div class="max-w-7xl mx-auto px-6 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">

        <div>
            <h1 class="text-3xl font-bold text-slate-900">
                Edit Product
            </h1>

            <p class="text-slate-500 mt-1">
                Update product information.
            </p>
        </div>


        <a href="{{ route('mi_app.show',$product->product_id) }}"
           class="px-5 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-50">
            Back
        </a>

    </div>


    {{-- Form --}}
    <form action="{{ route('mi_app.update',$product->product_id) }}"
          method="POST"
          enctype="multipart/form-data">

        @csrf
        @method('PUT')


        <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-6">


            {{-- SKU / Draft --}}
            <div class="grid md:grid-cols-2 gap-6">

                <div>
                    <label class="text-sm font-medium text-slate-700">
                        SKU Number
                    </label>

                    <input type="text"
                           value="{{ $product->sku }}"
                           disabled
                           class="mt-1 w-full rounded-lg border-slate-300 bg-slate-100">

                    <p class="text-xs text-slate-400 mt-1">
                        Auto generated
                    </p>
                </div>


                <div>
                    <label class="text-sm font-medium text-slate-700">
                        Draft Number
                    </label>

                    <input type="text"
                           value="{{ $product->draft_number }}"
                           disabled
                           class="mt-1 w-full rounded-lg border-slate-300 bg-slate-100">

                    <p class="text-xs text-slate-400 mt-1">
                        Auto generated
                    </p>
                </div>

            </div>



            {{-- Item --}}
            <div>

                <label class="text-sm font-medium">
                    Item Name
                </label>

                <input type="text"
                       name="item_name"
                       value="{{ old('item_name',$product->item_name) }}"
                       class="mt-1 w-full rounded-lg border-slate-300">

            </div>



            {{-- Taxonomy --}}
            <div class="grid md:grid-cols-4 gap-5">


                <div>
                    <label>Category</label>

                    <select name="category_id"
                            class="w-full rounded-lg border-slate-300">

                        @foreach($categories as $category)

                        <option value="{{ $category->id }}"
                            {{ $product->category_id == $category->id ? 'selected':'' }}>

                            {{ $category->name }}

                        </option>

                        @endforeach

                    </select>
                </div>



                <div>
                    <label>Sub Category</label>

                    <select name="sub_category_id"
                            class="w-full rounded-lg border-slate-300">

                        @foreach($subCategories as $sub)

                        <option value="{{ $sub->id }}"
                        {{ $product->sub_category_id == $sub->id ? 'selected':'' }}>

                            {{ $sub->name }}

                        </option>

                        @endforeach

                    </select>

                </div>



                <div>
                    <label>Product Type</label>

                    <select name="product_type_id"
                            class="w-full rounded-lg border-slate-300">

                        <option value="">
                            Select
                        </option>

                        @foreach($productTypes as $type)

                        <option value="{{ $type->id }}"
                        {{ $product->product_type_id == $type->id ? 'selected':'' }}>

                            {{ $type->name }}

                        </option>

                        @endforeach

                    </select>

                </div>



                <div>
                    <label>Collection</label>

                    <select name="collection_id"
                            class="w-full rounded-lg border-slate-300">

                        <option value="">
                            Select
                        </option>


                        @foreach($collections as $collection)

                        <option value="{{ $collection->id }}"
                        {{ $product->collection_id == $collection->id ? 'selected':'' }}>

                            {{ $collection->name }}

                        </option>

                        @endforeach


                    </select>

                </div>


            </div>




            {{-- Details --}}
            <div class="grid md:grid-cols-2 gap-6">


                <div>
                    <label>
                        Type of Sample
                    </label>

                    <input type="text"
                           name="type_of_sample"
                           value="{{ old('type_of_sample',$product->type_of_sample) }}"
                           class="w-full rounded-lg border-slate-300">

                </div>


                <div>
                    <label>
                        Designed By
                    </label>

                    <input type="text"
                           name="designed_by"
                           value="{{ old('designed_by',$product->designed_by) }}"
                           class="w-full rounded-lg border-slate-300">

                </div>


            </div>




            {{-- Dimensions --}}
            <h2 class="font-semibold text-lg mt-6">
                Product Dimensions
            </h2>


            <div class="grid md:grid-cols-4 gap-4">


                @foreach([
                'product_height'=>'Height',
                'product_width'=>'Width',
                'product_length'=>'Length',
                'product_depth'=>'Depth'
                ] as $field=>$label)


                <div>

                    <label>
                        {{ $label }}
                    </label>

                    <input type="number"
                           step="0.01"
                           name="{{ $field }}"
                           value="{{ $product->$field }}"
                           class="w-full rounded-lg border-slate-300">

                </div>


                @endforeach


            </div>




            {{-- Carton --}}
            <h2 class="font-semibold text-lg mt-6">
                Carton Dimensions
            </h2>


            <div class="grid md:grid-cols-4 gap-4">


                @foreach([
                'carton_height'=>'Height',
                'carton_width'=>'Width',
                'carton_length'=>'Length',
                'carton_depth'=>'Depth'
                ] as $field=>$label)


                <div>

                    <label>
                        {{ $label }}
                    </label>

                    <input type="number"
                           step="0.01"
                           name="{{ $field }}"
                           value="{{ $product->$field }}"
                           class="w-full rounded-lg border-slate-300">

                </div>


                @endforeach


            </div>





            {{-- Cost --}}
            <div>

                <label>
                    Purchase Cost
                </label>

                <input type="number"
                       step="0.01"
                       name="purchase_cost"
                       value="{{ $product->purchase_cost }}"
                       class="w-full rounded-lg border-slate-300">

            </div>





            {{-- Image --}}
            <div>

                <label>
                    Replace Product Image
                </label>

                <input type="file"
                       name="product_file"
                       class="w-full mt-2">


                @if($product->product_file)

                <img src="{{ asset('storage/'.$product->product_file) }}"
                     class="mt-4 w-40 rounded-lg border">

                @endif


            </div>




            {{-- Button --}}
            <div class="flex justify-end gap-3 pt-6">


                <a href="{{ route('mi_app.index') }}"
                   class="px-5 py-2.5 rounded-lg border">
                    Cancel
                </a>


                <button
                    class="px-5 py-2.5 bg-blue-600 text-white rounded-lg">

                    Update Product

                </button>


            </div>



        </div>


    </form>


</div>

</x-mi_app>