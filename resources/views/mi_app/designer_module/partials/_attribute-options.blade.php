@php
    $materialGroups = [

        'Solid Wood' => [
            'Solid Wood',
            'Acacia Wood',
            'Ash Wood',
            'Beech Wood',
            'Birch Wood',
            'Mahogany Wood',
            'Mango Wood',
            'Oak Wood',
            'Pine Wood',
            'Rubberwood',
            'Teak Wood',
            'Walnut Wood',
        ],

        'Wood Veneer' => [
            'Veneer',
            'Acacia Veneer',
            'Ash Veneer',
            'Birch Veneer',
            'Burl Veneer',
            'Oak Veneer',
            'Rubberwood Veneer',
            'Walnut Veneer',
            'White Mango Veneer',
            'Veneered MDF',
            'Acacia Veneered MDF',
            'Ash Veneered MDF',
            'Oak Veneered MDF',
        ],

        'Engineered Wood' => [
            'MDF',
            'HDF',
            'Particle Board',
            'Plywood',
            'Pine Plywood',
            'Melamine Board',
            'Wood Panel',
        ],

        'Metal' => [
            'Metal',
            'Steel',
            'Stainless Steel',
            'Iron',
            'Cast Iron',
            'Aluminum',
            'Brass',
            'Metal Frame',
            'Steel Frame',
            'Metal Base',
            'Metal Plate',
            'Metal Bar',
            'Metal Tube',
            'Metal Round Bar',
            'Metal Round Tube',
            'Ordinary Square Tube Metal',
            'Powdercoated Metal',
            'Black Powdercoated Metal',
            'Powdercoated Metal Frame',
            'Powdercoated Metal Base',
            'Powdercoated Round Bar Metal Frame',
            'Powdercoated Round Tube Metal Frame',
        ],

        'Metal Hardware & Fittings' => [
            'Metal Hardware',
            'Metal Hinges',
            'Metal Door Handle',
            'Metal Knob',
            'Metal Ring Handle',
            'Metal Holder',
            'Metal Drawer Slides',
            'Locks',
            'Casters',
        ],

        'Glass' => [
            'Glass',
            'Clear Glass',
            'Tempered Glass',
            'Embossed Clear Glass',
            'Glass Panel',
            'Glass Window Panel',
        ],

        'Stone, Marble & Ceramic' => [
            'Natural Stone',
            'Stone Cast',
            'Marble',
            'Faux Marble',
            'Granite',
            'Ceramic',
            'Concrete',
        ],

        'Rattan' => [
            'Rattan',
            'Rattan Pole',
            'Rattan Core',
            'Rattan Splits',
            'Rattan Frame',
            'Rattan Weave',
            'Rattan Cane',
            'Rattan Cane Mat',
            'Natural Rattan Cane Mat',
            'Open Mesh Rattan Cane',
        ],

        'Wicker & Cane' => [
            'Wicker',
            'Cane',
            'Natural Cane',
            'Cane Weave',
            'Woven Cane',
            'Open Mesh Cane',
        ],

        'Seagrass' => [
            'Seagrass',
            'Seagrass Weave',
            'Seagrass Mat',
            'Seagrass Cover',
            'Twisted Seagrass',
        ],

        'Water Hyacinth' => [
            'Water Hyacinth',
            'Water Hyacinth Weave',
            'Water Hyacinth Braided Weave',
            'Water Hyacinth Cane Weave',
            'Water Hyacinth Mat',
        ],

        'Abaca' => [
            'Abaca',
            'Abaca Fiber',
            'Abaca Weave',
            'Abaca Mat',
        ],

        'Bamboo' => [
            'Bamboo',
            'Bamboo Pole',
            'Bamboo Weave',
        ],

        'Paper & Natural Fiber' => [
            'Paper',
            'Paper Weave',
            'Natural Fiber',
            'Raffia',
            'Raffia Mat',
            'Banana Leaf',
            'Corn Husk',
            'Twisted Grass',
        ],

        'Rope' => [
            'Natural Fiber Rope',
            'Twisted Paper Rope',
            'Seagrass Rope',
            'Water Hyacinth Rope',
            'Abaca Rope',
        ],

        'Fabric & Upholstery' => [
            'Fabric',
            'Boucle',
            'Canvas',
            'Cotton',
            'Linen',
            'Microfiber',
            'Polyester',
            'Velvet',
            'Leather',
            'PU Leather',
        ],

        'Padding & Filling' => [
            'Foam',
            'FR Foam',
            'Cushion',
        ],

        'Plastic & Synthetic' => [
            'Plastic',
            'ABS Plastic',
            'Acrylic',
            'Fiberglass',
            'Polypropylene',
            'PVC',
            'Plastic Strips',
            'Synthetic Material',
        ],

        'Resin' => [
            'Resin',
            'Resin Cast',
        ],

        'Shell & Decorative Inlay' => [
            'Shell',
            'Capiz Shell',
            'Capiz Inlay',
            'Mother of Pearl',
            'MOP Inlay',
        ],

        'Laminate & Finish' => [
            'Laminate',
            'PVC Laminate',
            'Painted Finish',
            'Glossy Lacquer',
            'White Glossy Lacquer',
            'Powdercoated Finish',
            'Wood Stain',
            'Gold Leaf',
        ],

        'Other' => [
            'Composite',
            'Mixed Materials',
        ],

    ];

$colorGroups = [

        'Basic Colors' => [
            'Black',
            'White',
            'Gray',
            'Silver',
            'Gold',
            'Bronze',
        ],

        'Wood Finishes' => [
            'Natural',
            'Oak',
            'Walnut',
            'Teak',
            'Mahogany',
            'Espresso',
        ],

        'Neutral' => [
            'Beige',
            'Cream',
            'Ivory',
            'Taupe',
            'Brown',
        ],

        'Accent Colors' => [
            'Blue',
            'Green',
            'Red',
            'Yellow',
            'Orange',
            'Pink',
            'Purple',
        ],

        'Special Finishes' => [
            'Matte Black',
            'Gloss White',
            'Brushed Gold',
            'Rose Gold',
            'Chrome',
        ],

    ];
    $groups = $attribute === 'materials' ? $materialGroups : $colorGroups;
    $knownValues = array_merge(...array_values($groups));
    $customValues = array_diff($selectedValues, $knownValues);
@endphp
@foreach($groups as $group => $choices)
    <optgroup label="{{ $group }}">
        @foreach($choices as $value)
            <option value="{{ $value }}" @selected(in_array($value, $selectedValues, true))>{{ $value }}</option>
        @endforeach
    </optgroup>
@endforeach
@if($customValues)
    <optgroup label="Saved custom values">
        @foreach($customValues as $value)<option selected value="{{ $value }}">{{ $value }}</option>@endforeach
    </optgroup>
@endif
