<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rubrik Penilaian - {{ $eventner->nama_event }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 5px; }
        h4 { text-align: center; margin-top: 0; color: #555; }
        .category-container { margin-top: 20px; }
        .category-title { background: #333; color: #fff; padding: 5px; font-weight: bold; }
        .subcategory-title { margin-top: 10px; font-weight: bold; padding: 5px; background: #eee; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f9f9f9; text-align: left; }
        .options { text-align: center; }
        .circle { 
            display: inline-block; 
            width: 25px; 
            height: 25px; 
            line-height: 25px; 
            border: 1px solid #aaa; 
            border-radius: 50%; 
            margin: 0 2px;
            font-size: 10px;
        }
    </style>
</head>
<body>

    <h2>FORMAT PENILAIAN</h2>
    <h4>Kegiatan: {{ $eventner->nama_event }} - {{ $eventner->diselenggarakan_oleh }}</h4>
    <hr>

    @if($categories->isEmpty())
        <p style="text-align:center;">Belum ada format penilaian yang dibangun.</p>
    @else
        @foreach($categories as $category)
            <div class="category-container">
                <div class="category-title">{{ $category->name }}</div>
                
                @foreach($category->subCategories as $subcat)
                    <div class="subcategory-title">{{ $subcat->name }}</div>
                    
                    @if($subcat->criterias->isNotEmpty())
                        <table>
                            <thead>
                                <tr>
                                    <th width="40%">Kriteria Penilaian</th>
                                    <th width="60%" class="options">Skor Penilaian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subcat->criterias as $crit)
                                <tr>
                                    <td>{{ $crit->name }}</td>
                                    <td class="options">
                                        @foreach($crit->score_options as $score)
                                            <span class="circle">{{ $score }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endforeach
            </div>
        @endforeach
    @endif

</body>
</html>
