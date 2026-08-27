<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <table style="width: 100%; border-collapse: collapse; border: 1px solid black;">
        <thead>
            <tr>
                <th colspan="2" style="text-align: center; font-weight: bold; font-size: 16px; border: 1px solid black; padding: 10px;">
                    Quotes Report — From {{ $startDate }} To {{ $endDate }}
                </th>
            </tr>
            <tr>
                <th style="border: 1px solid black; background-color: #e3e3e3; font-weight: bold; text-align: center;">Date</th>
                <th style="border: 1px solid black; background-color: #e3e3e3; font-weight: bold; text-align: center;">Qualified Leads</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyData as $date => $row)
            <tr>
                <td style="border: 1px solid black; text-align: center;">{{ $date }}</td>
                <td style="border: 1px solid black; text-align: center;">{{ $row['Qualified Leads'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #d9edf7;">
                <th style="border: 1px solid black; font-weight: bold; text-align: right;">TOTAL</th>
                <th style="border: 1px solid black; font-weight: bold; text-align: center;">{{ $totals['Qualified Leads'] }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
