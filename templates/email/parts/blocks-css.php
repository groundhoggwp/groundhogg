<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
.email-columns {
display: table;
table-layout: fixed;
width: 100%;
}
.email-columns .email-columns-row {
display: table-row;
}
.email-columns .email-columns-row  .email-columns-cell {
display: table-cell;
vertical-align: top;
}
@media only screen and (max-width: 480px) {
.email-columns,
.email-columns .email-columns-row,
.email-columns .email-columns-row .email-columns-cell{
display: block;
padding: 0 !important;
width: 100% !important;
}
}
