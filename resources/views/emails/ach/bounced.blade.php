<!DOCTYPE html PUBLIC>

<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <title>SoftPayPlus</title>
    </head>

    <body style="text-align:center;margin:30px;font-family:Arial, Helvetica, sans-serif;" bgcolor="#F7F7F7">
        <table width="670" border="0" align="center" cellpadding="0" cellspacing="0"
               style="background-color: #fff; border-collapse: collapse">
            <tr>
                <td height="100" align="left" valign="middle"
                    style="border-bottom:1px solid #f3f3f3 ;padding-left:40px">
                    <a href="https://www.softpayplus.com" target="_blank"><img
                                src="https://www.softpayplus.com/img/brand/logo-black.png" border="0"/></a></td>
            </tr>
            <tr>
                <td height="55" align="center" valign="top">
                    <table width="590" border="0" cellspacing="0" cellpadding="0" style="border-collapse: collapse">
                        <tr>
                            <td width="430" height="70" align="left" valign="middle"><span
                                        style="font-size:14px; color:#111">Dear, </span><span
                                        style="font-size:14px; color:#0144b3;">{{ $ach->account->contact }}</span></td>
                            <td width="160" height="70" align="right" valign="middle"
                                style="font-size:12px; color:#666; font-weight:bold;">{{ \Carbon\Carbon::today()->format('Y-m-d') }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <table width="100%" border="0" cellspacing="0" cellpadding="0"
                                       style="border-collapse: collapse">
                                    <tr>
                                        <td height="34" align="left" valign="top"
                                            style="font-size:17px; color:#111; font-weight:bold;"><span
                                                    style="font-family:Arial, Helvetica, sans-serif; font-size:17px; color:#111; font-weight:bold;">Notice:</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="34" align="left" valign="top"
                                            style="font-family:Arial, Helvetica, sans-serif; font-size:15px; color:#111; padding:0px 15px 15px 0px;">
                                            This account has been bounced, and deactivated right now.
                                            To reactivate the account or update the ACH bank information, please contact your contact person.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family:Arial, Helvetica, sans-serif; font-size:14px; color:#111; line-height: 20px;">
                                            <table width="100%" border="0" align="center" cellpadding="0"
                                                   cellspacing="0" bgcolor="#FFFFFF" style="border-collapse: collapse">
                                                <tr>
                                                    <td width="185" height="50" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:20px; border-top:1px solid #c1bfbf; border-left:1px solid #c1bfbf; border-right:1px solid #c1bfbf; text-align: left; font-weight:bold; background-color:#f3f3f3;">
                                                        Category:
                                                    </td>
                                                    <td height="47" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:10px; padding-right:10px; border-top:1px solid #c1bfbf; border-right:1px solid #c1bfbf;">
                                                        {{ $ach->type_name }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="185" height="50" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:20px; border-top:1px solid #c1bfbf; border-left:1px solid #c1bfbf; border-right:1px solid #c1bfbf; text-align: left; font-weight:bold; background-color:#f3f3f3;">
                                                        Account #:
                                                    </td>
                                                    <td height="47" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:10px; padding-right:10px; border-top:1px solid #c1bfbf; border-right:1px solid #c1bfbf;">
                                                        {{ $ach->account_id }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="185" height="50" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:20px; border-top:1px solid #c1bfbf; border-left:1px solid #c1bfbf; border-right:1px solid #c1bfbf; text-align: left; font-weight:bold; background-color:#f3f3f3;">
                                                        Account Type:
                                                    </td>
                                                    <td height="47" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:10px; padding-right:10px; border-top:1px solid #c1bfbf; border-right:1px solid #c1bfbf;">
                                                        {{ $ach->account->type_name() }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="185" height="50" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:20px; border-top:1px solid #c1bfbf; border-left:1px solid #c1bfbf; border-right:1px solid #c1bfbf; text-align: left; font-weight:bold; background-color:#f3f3f3;">
                                                        Account Name:
                                                    </td>
                                                    <td height="47" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:10px; padding-right:10px; border-top:1px solid #c1bfbf; border-right:1px solid #c1bfbf;">
                                                        {{{ $ach->account->name }}}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="185" height="50" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:20px; border-top:1px solid #c1bfbf; border-left:1px solid #c1bfbf; border-right:1px solid #c1bfbf; text-align: left; font-weight:bold; background-color:#f3f3f3;">
                                                        {{ $ach->amt > 0 ? 'Debit' : 'Credit' }} Amount:
                                                    </td>
                                                    <td height="47" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:10px; padding-right:10px; border-top:1px solid #c1bfbf; border-right:1px solid #c1bfbf;">
                                                        $ {{ number_format($ach->amt, 2) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="185" height="50" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:20px; border-top:1px solid #c1bfbf; border-left:1px solid #c1bfbf; border-right:1px solid #c1bfbf; text-align: left; font-weight:bold; background-color:#f3f3f3;">
                                                        Time of Bounce:
                                                    </td>
                                                    <td height="47" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:10px; padding-right:10px; border-top:1px solid #c1bfbf; border-right:1px solid #c1bfbf;">
                                                        {{ $ach->bounce_date }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="185" height="50" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:20px; border-top:1px solid #c1bfbf; border-left:1px solid #c1bfbf; border-right:1px solid #c1bfbf; text-align: left; font-weight:bold; background-color:#f3f3f3;">
                                                        Details:
                                                    </td>
                                                    <td height="47" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:10px; padding-right:10px; border-top:1px solid #c1bfbf; border-right:1px solid #c1bfbf;">
                                                        {{ $ach->bounce_msg }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="185" height="50" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:20px; border-top:1px solid #c1bfbf; border-left:1px solid #c1bfbf; border-right:1px solid #c1bfbf; text-align: left; font-weight:bold; background-color:#f3f3f3;">
                                                        Master :
                                                    </td>
                                                    <td height="47" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:10px; padding-right:10px; border-top:1px solid #c1bfbf; border-right:1px solid #c1bfbf;">
                                                        {{ $ach->account->master->name }}({{ $ach->account->master->id }})
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="185" height="50" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:20px; border-top:1px solid #c1bfbf; border-left:1px solid #c1bfbf; border-right:1px solid #c1bfbf; text-align: left; font-weight:bold; background-color:#f3f3f3;border-bottom:1px solid #c1bfbf;">
                                                        <span style="color: #ff4629">Bounce Fee:</span></td>
                                                    <td height="47" align="left" valign="middle"
                                                        style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; padding-left:10px; padding-right:10px; border-top:1px solid #c1bfbf; border-right:1px solid #c1bfbf;border-bottom:1px solid #c1bfbf;">
                                                        <span style="color: #ff4629"><strong>$ {{ $ach->bounce_fee }}</strong></span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="left" valign="top"
                                            style="font-family:Arial, Helvetica, sans-serif; font-size:14px; color:#111; padding:25px 15px;">
                                            Sincerely,<br>
                                            SoftPayPlus Customer Care
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td height="70" colspan="2" align="center" valign="middle"
                    style="font-size:11px; color:#666666; font-weight:normal;border-top:2px solid #f3f3f3">
                    &copy;2010 <a href="https://www.softpayplus.com/" target="_blank"
                                  style="font-size:11px; color:#ff4629; font-weight:bold; text-decoration:none;">SoftPayPlus.com</a>.
                    All Rights Reserved.
                </td>
            </tr>

        </table>
    </body>
</html>
