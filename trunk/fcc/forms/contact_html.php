
<style type="text/css">
body {
	font-size: 12px;
	font-family: arial;
}
.form-uno {
	padding: 5px;
	background-color: #99cefa;
}

.form-due {
	padding: 5px;
}

.form-due h2 {
	font-size: 16px;
	font-family: arial;
}

fieldset {
	margin: 5px;
	border: 1px solid black;
	padding: 5px;
}

fieldset:hover {
	background-color: #cefa99;
}

label,input {
	display: block;
	
	float: left;
	margin-bottom: 10px;
}

label {
	text-align: right;
	width: 75px;
	padding-right: 20px;
}

br {
	clear: left;
}

.fcc_error {
	border: 1px solid red;
	background-color: #ffdddd;
	padding: 2px;
}

.fcc_error h2 {
	color: red;
	font-size: 16px;
}

</style>

<form method="POST" action="">
<fieldset class="form-uno">
	<label for="ditta_commisionante">Ditta che commissiona il lavoro</label><input type="text" id="ditta_commisionante" name="fcc[ditta_commisionante]" value="" />
	<label for="codice_cliente">Codice cliente</label><input type="text" id="codice_cliente" name="fcc[codice_cliente]" value="" />
</fieldset>
<br/>
<fieldset class="form-due">
	<h2>Luogo di ritiro</h2>
	<label for="fcc[ritiro_ditta]">Ditta/Ragione sociale</label><input type="text" id="fcc[ritiro_ditta]" name="fcc[ritiro_ditta]" value="" /><br/>
	<label for="fcc[ritiro_via]">Via</label><input type="text" id="fcc[ritiro_via]" name="fcc[ritiro_via]" value="" /><br/>
	<label for="fcc[ritiro_localita]">Località</label><input type="text" id="fcc[ritiro_localita]" name="fcc[ritiro_localita]" value="" />
	<label for="fcc[ritiro_cap]">CAP</label><input type="text" id="fcc[ritiro_cap]" name="fcc[ritiro_cap]" value="" /><br/>
	<label for="fcc[ritiro_prov]">Provincia</label><input type="text" id="fcc[ritiro_prov]" name="fcc[ritiro_prov]" value="" /><br/>
	<label for="fcc[ritiro_tipo]">Tipologia di ritiro</label><select id="fcc[ritiro_tipo]" name="fcc[ritiro_tipo]"><option value="industria">Industria</option><option value="gdo">GDO</option><option value="privato">Privato</option></select>
</fieldset>
<br/>
<fieldset class="form-due">
	<h2>Luogo di destinazione</h2>
	<label for="fcc[dest_ditta]">Ditta/Ragione sociale</label><input type="text" id="fcc[dest_ditta]" name="fcc[dest_ditta]" value="" /><br/>
	<label for="fcc[dest_via]">Via</label><input type="text" id="fcc[dest_via]" name="fcc[dest_via]" value="" /><br/>
	<label for="fcc[dest_localita]">Località</label><input type="text" id="fcc[dest_localita]" name="fcc[dest_localita]" value="" />
	<label for="fcc[dest_cap]">CAP</label><input type="text" id="fcc[dest_cap]" name="fcc[dest_cap]" value="" /><br/>
	<label for="fcc[dest_prov]">provincia</label><input type="text" id="fcc[dest_prov]" name="fcc[dest_prov]" value="" /><br/>
	<label for="fcc[dest_tipo]">Tipologia di destinazione</label><select id="fcc[dest_tipo]" name="fcc[dest_tipo]"><option value="industria">Industria</option><option value="gdo">GDO</option><option value="privato">Privato</option></select>
</fieldset>
<br/>
<fieldset class="form-due">
	<h2>Giorno ed ora di ritiro</h2>
	<label for="fcc[ritiro_giorno]">Giorno del ritiro</label><input type="text" id="fcc[ritiro_giorno]" name="fcc[ritiro_giorno]" value="" />
	<label for="fcc[ritiro_ora]">Orario preferito</label><input type="text" id="fcc[ritiro_ora]" name="fcc[ritiro_ora]" value="" />	<br/>
	<label for="fcc[consegna_entro]">Consegna entro</label><input type="text" id="fcc[consegna_entro]" name="fcc[consegna_entro]" value="" />	
</fieldset>
<br/>
<fieldset class="form-due">
	<h2>Informazioni</h2>
	<label for="fcc[colli_num]">Numero Colli</label><input type="text" id="fcc[colli_num]" name="fcc[colli_num]" value="" />
	<label for="fcc[colli_peso]">Peso Kg</label><input type="text" id="fcc[colli_peso]" name="fcc[colli_peso]" value="" /><br/>
	<label for="fcc[colli_bancali]">Numero Bancali</label><input type="text" id="fcc[colli_bancali]" name="fcc[colli_bancali]" value="" />
	<label for="fcc[colli_mq]">Volume Mq</label><input type="text" id="fcc[colli_mq]" name="fcc[colli_mq]" value="" />
</fieldset>
<br/>
<fieldset class="form-due">
<h2>Note:</h2>
<textarea name="fcc[note]" cols="70" rows="10"></textarea>
</fieldset>
<br/>
<input type="hidden" name="check[required]" value="ditta_commisionante,ritiro_ditta,ritiro_via,ritiro_localita,ritiro_cap,ritiro_prov,ritiro_tipo" />
<input type="hidden" name="check[integer]" value="colli_num,colli_peso" />
<input type="hidden" name="check[max][2]" value="colli_num,colli_peso" />

<input type="submit" name="invia" />
</form>
