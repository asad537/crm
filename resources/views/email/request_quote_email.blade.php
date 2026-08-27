 
<!DOCTYPE html>
<html>
<head>
      <title></title>
      <style type="text/css">
            #customers {
  font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

#customers td, #customers th {
  border: 1px solid #ddd;
  padding: 8px;
}

#customers tr:nth-child(even){background-color: #f2f2f2;}

#customers tr:hover {background-color: #ddd;}

#customers th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
  background-color: #4CAF50;
  color: white;
}
</style>
      </style>
</head>
<body>

<table id="customers">
      <th>Name</th>
      <th>Email</th>
      <th>Phone Number</th>
      <th>Length</th>
      <th>Width</th>
      <th>Height</th>
      <th>Stock</th>
      <th>Color</th>
      <th>Quantity 1</th>
      <th>Quantity 2</th>
      <th>Quantity 3</th>
      <th>Product Name</th>
      <!--<th>File</th>-->
      <th>Subject</th>
      <tr>
<td>{{$data['name']}}</td>
<td>{{$data['email']}}</td>
<td>{{$data['phone']}}</td>
<td>{{$data['length']}}</td>
<td>{{$data['width']}}</td>
<td>{{$data['height']}}</td>
<td>{{$data['stock']}}</td>
<td>{{$data['color']}}</td>
<td>{{$data['qty1']}}</td>
<td>{{$data['qty2']}}</td>
<td>{{$data['qty3']}}</td>
<td>{{$data['box_style']}}</td>

<td>{{$data['subject']}}</td>
</tr>
</table>
</body>
</html>