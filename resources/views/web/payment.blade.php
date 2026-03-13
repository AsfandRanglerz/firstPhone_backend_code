<form id="apg" method="POST"
action="https://payments.bankalfalah.com/SSO/SSO/SSO">

<input type="hidden" name="AuthToken" value="{{ $authToken }}">
<input type="hidden" name="RequestHash" value="{{ $requestHash }}">
<input type="hidden" name="ChannelId" value="{{ env('APG_CHANNEL_ID') }}">
<input type="hidden" name="Currency" value="PKR">
<input type="hidden" name="ReturnURL" value="{{ env('APG_RETURN_URL') }}">
<input type="hidden" name="MerchantId" value="{{ env('APG_MERCHANT_ID') }}">
<input type="hidden" name="StoreId" value="{{ env('APG_STORE_ID') }}">
<input type="hidden" name="MerchantHash" value="{{ env('APG_MERCHANT_HASH') }}">
<input type="hidden" name="MerchantUsername" value="{{ env('APG_USERNAME') }}">
<input type="hidden" name="MerchantPassword" value="{{ env('APG_PASSWORD') }}">
<input type="hidden" name="TransactionTypeId" value="3">
<input type="hidden" name="TransactionReferenceNumber" value="{{ $reference }}">
<input type="hidden" name="TransactionAmount" value="{{ $amount }}">

</form>

<script>
document.getElementById("apg").submit();
</script>