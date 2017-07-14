<a href="{{ syncButton.href }}" class="button pacsoft-button pacsoft-icon-sync syncOrderToPacsoft" data-order="{{ orderId }}" data-service="{{ serviceId }}" title="{{ syncButton.title }}" ></a>
<a href="{{ printButton.href }}" class="button pacsoft-button pacsoft-icon-print printPacsoftOrder" data-order-id="{{ orderId }}" data-nonce="{{ nonce }}" title="{{ printButton.title }}"></a>
<span class="pacsoft-status pacsoft-icon-{{# isSynced }}tick{{/ isSynced }}{{^ isSynced }}cross{{/ isSynced }}"></span>
<span class="spinner pacsoft-spinner"></span>