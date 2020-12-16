<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>RECEIPT | SoftPayPlus</title>

  <!-- Normalize or reset CSS with your favorite library -->
  <link rel="stylesheet" href="/css/receipt/normalize.css">

  <!-- Load paper.css for happy printing -->
  <link rel="stylesheet" href="/css/receipt/paper.css">

  <!-- Set page size here: A5, A4 or A3 -->
  <!-- Set also "landscape" if you need -->
  <style>@page { size: RS }</style>

  <!-- Custom styles for this document -->
  <link href='https://fonts.googleapis.com/css?family=Tangerine:700' rel='stylesheet' type='text/css'>
  <style>
    body   { font-family: serif }
    h1     { font-family: serif; font-size: 40pt; line-height: 18mm}
    h2, h3 { font-family: serif; font-size: 24pt; line-height: 7mm }
    h4     { font-size: 32pt; line-height: 14mm }
    h2 + p { font-size: 18pt; line-height: 7mm }
    h3 + p { font-size: 14pt; line-height: 7mm }
    li     { font-size: 11pt; line-height: 5mm }
    h1      { margin: 0 }
    h1 + ul { margin: 2mm 0 5mm }
    h2, h3  { margin: 0 3mm 3mm 0; float: left }
    h2 + p,
    h3 + p  { margin: 0 0 3mm 50mm }
    h4      { margin: 2mm 0 0 50mm; border-bottom: 2px solid black }
    h4 + ul { margin: 5mm 0 0 50mm }
    article { border: 4px double black; padding: 5mm 10mm; border-radius: 3mm }
    td { padding: 2px; border: 0; }
  </style>
</head>

<!-- Set "A5", "A4" or "A3" for class name -->
<!-- Set also "landscape" if you need -->
<body class="RS">

  <!-- Each sheet element should have the class "sheet" -->
  <!-- "padding-**mm" is optional: you can set 10, 15, 20 or 25 -->
  <section class="sheet padding-15px">

    <h2>SoftPayPlus</h2>
    {{ $trans->cdate }}
    <hr>

    <div style="width:100%; text-align: center;">{{ $trans->product->name }}</div>
    <hr>
    <table style="width:100%;">
      <tr>
        <td style="width: 100%; text-align: left;">Order #.</td>
      </tr>
      <tr>
        <td style="width: 100%; text-align: right">{{ $trans->id }}</td>
      </tr>
    </table>
    <table style="width:100%;">
      <tr>
        <td style="width: 100%; text-align: left;">Action.</td>
      </tr>
      <tr>
        <td style="width: 100%; text-align: right">{{ $trans->action }}</td>
      </tr>
    </table>
    @if (!empty($trans->phone))
    <table style="width:100%;">
      <tr>
        <td style="width: 100%; text-align: left;">Phone no.</td>
      </tr>
      <tr>
        <td style="width: 100%; text-align: right">{{ $trans->phone }}</td>
      </tr>
    </table>
    @endif
    @if (!empty($trans->pin))
    <table style="width:100%;">
      <tr>
        <td style="width: 100%; text-align: left;">PIN.</td>
      </tr>
      <tr>
        <td style="width: 100%; text-align: right">{{ $trans->pin }}</td>
      </tr>
    </table>
    @endif
    @if (!empty($trans->sim))
    <table style="width:100%;">
      <tr>
        <td style="width: 100%; text-align: left;">SIM</td>
      </tr>
      <tr>
        <td style="width: 100%; text-align: right">{{ $trans->sim }}</td>
      </tr>
    </table>
    @endif
    @if (!empty($trans->esn))
    <table style="width:100%;">
      <tr>
        <td style="width: 100%; text-align: left;">ESN</td>
      </tr>
      <tr>
        <td style="width: 100%; text-align: right">{{ $trans->esn }}</td>
      </tr>
    </table>
    @endif
    @if (!empty($trans->msid))
      <table style="width:100%;">
        <tr>
          <td style="width: 100%; text-align: left;">MSID</td>
        </tr>
        <tr>
          <td style="width: 100%; text-align: right">{{ $trans->msid }}</td>
        </tr>
      </table>
    @endif
    @if (!empty($trans->msl))
      <table style="width:100%;">
        <tr>
          <td style="width: 100%; text-align: left;">MSL</td>
        </tr>
        <tr>
          <td style="width: 100%; text-align: right">{{ $trans->msl }}</td>
        </tr>
      </table>
    @endif
    @if (!empty($trans->denom))
    <table style="width:100%;">
      <tr>
        <td style="width: 100%; text-align: left;">Plan</td>
      </tr>
      <tr>
        <td style="width: 100%; text-align: right">$ {{ number_format($trans->denom, 2) }}</td>
      </tr>
    </table>
    @endif
    <table style="width:100%;">
      <tr>
        <td style="width: 100%; text-align: left;">Fee</td>
      </tr>
      <tr>
        <td style="width: 100%; text-align: right">$ {{ number_format($trans->fee + $trans->pm_fee, 2) }}</td>
      </tr>
    </table>
    <hr>
    <table style="width:100%;">
      <tr>
        <td style="width:50%">Price:</td><td style="text-align: right">$ {{ number_format($trans->denom + $trans->fee + $trans->pm_fee, 2) }}</td>
      </tr>
    </table>
    <hr>

  </section>
  <script type="text/javascript" src="/js/jquery.min.js"></script>

  <script type="text/javascript">
    $( document ).ready(function() {
        window.print();
    });
  </script>

</body>

</html>