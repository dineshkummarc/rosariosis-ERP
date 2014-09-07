<?php

function DeleteTransactionItem($transaction_id,$item_id,$type='student')
{
	if($_REQUEST['type']=='staff')
	{
		$sql1 = "UPDATE FOOD_SERVICE_STAFF_TRANSACTIONS SET BALANCE=BALANCE-(SELECT AMOUNT FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID='".$transaction_id."' AND ITEM_ID='".$item_id."') WHERE TRANSACTION_ID>='".$transaction_id."' AND STAFF_ID=(SELECT STAFF_ID FROM FOOD_SERVICE_STAFF_TRANSACTIONS WHERE TRANSACTION_ID='".$transaction_id."')";
		$sql2 = "UPDATE FOOD_SERVICE_STAFF_ACCOUNTS SET BALANCE=BALANCE-(SELECT AMOUNT FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID='".$transaction_id."' AND ITEM_ID='".$item_id."') WHERE STAFF_ID=(SELECT STAFF_ID FROM FOOD_SERVICE_STAFF_TRANSACTIONS WHERE TRANSACTION_ID='".$transaction_id."')";
		$sql3 = "DELETE FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID='".$transaction_id."' AND ITEM_ID='".$item_id."'";
	}
	else
	{
		$sql1 = "UPDATE FOOD_SERVICE_TRANSACTIONS SET BALANCE=BALANCE-(SELECT AMOUNT FROM FOOD_SERVICE_TRANSACTION_ITEMS WHERE TRANSACTION_ID='".$transaction_id."' AND ITEM_ID='".$item_id."') WHERE TRANSACTION_ID>='".$transaction_id."' AND ACCOUNT_ID=(SELECT ACCOUNT_ID FROM FOOD_SERVICE_TRANSACTIONS WHERE TRANSACTION_ID='".$transaction_id."')";
		$sql2 = "UPDATE FOOD_SERVICE_ACCOUNTS SET BALANCE=BALANCE-(SELECT AMOUNT FROM FOOD_SERVICE_TRANSACTION_ITEMS WHERE TRANSACTION_ID='".$transaction_id."' AND ITEM_ID='".$item_id."') WHERE ACCOUNT_ID=(SELECT ACCOUNT_ID FROM FOOD_SERVICE_TRANSACTIONS WHERE TRANSACTION_ID='".$transaction_id."')";
		$sql3 = "DELETE FROM FOOD_SERVICE_TRANSACTION_ITEMS WHERE TRANSACTION_ID='".$transaction_id."' AND ITEM_ID='".$item_id."'";
	}
	DBQuery('BEGIN; '.$sql1.'; '.$sql2.'; '.$sql3.'; COMMIT');
	
	//modif Francois: if no more transaction items, delete transaction
	$trans_items_RET = DBGet(DBQuery("SELECT 1 FROM ".($_REQUEST['type']=='staff' ? "FOOD_SERVICE_STAFF_TRANSACTION_ITEMS" :  "FOOD_SERVICE_TRANSACTION_ITEMS")." WHERE TRANSACTION_ID='".$transaction_id."'"));
	if (!is_null($trans_items_RET))
	{
		require_once('modules/Food_Service/includes/DeleteTransaction.fnc.php');
		DeleteTransaction($transaction_id,$type);
	}
}
?>