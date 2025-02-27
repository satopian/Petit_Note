//	BBS Note 動的パレット＆マトリクス 2003/06/22
//	(C) のらネコ WonderCatStudio http://wondercatstudio.com/
//substr()→substring()対策版 by satopian
"use strict";
var DynamicColor = 1;	// パレットリストに色表示
var Palettes = new Array();
// ========== パレット配列作成 ==========
// {$palettes}
var d, p, s, m, t, n, o, e, i, c, l, pe, ps, se;
Palettes[0] = "#000000\n#FFFFFF\n#B47575\n#888888\n#FA9696\n#C096C0\n#FFB6FF\n#8080FF\n#25C7C9\n#E7E58D\n#E7962D\n#99CB7B\n#FCECE2\n#F9DDCF";Palettes[1] = "#FFF0DC\n#52443C\n#FFE7D0\n#5E3920\n#FFD6C0\n#B06A54\n#FFCBB3\n#C07A64\n#FFC0A3\n#DEA197\n#FFB7A2\n#ECA385\n#000000\n#FFFFFF";Palettes[2] = "#FFEEF7\n#FFE6E6\n#FFCAE4\n#FFC4C4\n#FF9DCE\n#FF7D7D\n#FF6AB5\n#FF5151\n#FF2894\n#FF0000\n#CF1874\n#BF0000\n#851B53\n#800000";Palettes[3] = "#FFE3D7\n#FFFFDD\n#FFCBB3\n#FFFFA2\n#FFA275\n#FFFF00\n#FF8040\n#D9D900\n#FF5F11\n#AAAA00\n#DB4700\n#7D7D00\n#BD3000\n#606000";Palettes[4] = "#C6FDD9\n#E8FACD\n#8EF09F\n#B9E97E\n#62D99D\n#9ADC65\n#1DB67C\n#65B933\n#1A8C5F\n#4F8729\n#136246\n#2B6824\n#0F3E2B\n#004000";Palettes[5] = "#DFF4FF\n#C1FFFF\n#80C6FF\n#6DEEFC\n#60A8FF\n#44D0EE\n#1D56DC\n#209CCC\n#273D8F\n#2C769A\n#1C2260\n#295270\n#000040\n#003146";Palettes[6] = "#E9D2FF\n#E1E1FF\n#DAB5FF\n#C1C1FF\n#CE9DFF\n#8080FF\n#B366FF\n#6262FF\n#9428FF\n#3D44C9\n#6900D2\n#33309E\n#3F007D\n#252D6B";Palettes[7] = "#ECD3BD\n#F7E2BD\n#E4C098\n#DBC7AC\n#C8A07D\n#D9B571\n#896952\n#C09450\n#825444\n#AE7B3E\n#5E4435\n#8E5C2F\n#493830\n#5F492C";Palettes[8] = "#FFEADD\n#DED8F5\n#FFCAAB\n#9C89C4\n#F19D71\n#CF434A\n#52443C\n#F09450\n#5BADFF\n#FDF666\n#0077D9\n#4AA683\n#000000\n#FFFFFF";Palettes[9] = "#F6CD8A\n#FFF99D\n#89CA9D\n#C7E19E\n#8DCFF4\n#8CCCCA\n#9595C6\n#94AAD6\n#AE88B8\n#9681B7\n#F49F9B\n#F4A0BD\n#8C6636\n#FFFFFF";Palettes[10] = "#C7E19E\n#D1E1FF\n#A8D59D\n#8DCFE0\n#7DC622\n#00A49E\n#528413\n#CBB99C\n#00B03B\n#766455\n#007524\n#5B3714\n#0F0F0F\n#FFFFFF";Palettes[11] = "#FFFF80\n#F4C1D4\n#EE9C00\n#F4BDB0\n#C45914\n#ED6B9E\n#FEE7DB\n#E76568\n#FFC89D\n#BD3131\n#ECA385\n#AE687E\n#0F0F0F\n#FFFFFF";Palettes[12] = "#FFFFFF\n#7F7F7F\n#EFEFEF\n#5F5F5F\n#DFDFDF\n#4F4F4F\n#CFCFCF\n#3F3F3F\n#BFBFBF\n#2F2F2F\n#AFAFAF\n#1F1F1F\n#0F0F0F\n#000000";
function setPalette(){
	d = document
	d.paintbbs.setColors(Palettes[d.Palette.select.selectedIndex])
	if(! d.grad.view.checked){return}
	GetPalette();
}
async function PaletteSave(){
	Palettes[0] = String(await document.paintbbs.getColors())
}
var cutomP = 0;
async function PaletteNew(){
	d = document
	p = String(await d.paintbbs.getColors())
	s = d.Palette.select
	Palettes[s.length] = p
	cutomP++
	const str = prompt("Palette name","Palette " + cutomP)
	if(str == null || str == ""){cutomP--;return}
	s.options[s.length] = new Option(str)
	if(30 > s.length) s.size = s.length
	PaletteListSetColor()
}
async function PaletteRenew(){
	d = document
	Palettes[d.Palette.select.selectedIndex] = String(await d.paintbbs.getColors())
	PaletteListSetColor()
}
function PaletteDel(){
	p = Palettes.length
	s = document.Palette.select
	i = s.selectedIndex
	if(i == -1)return
	const flag = confirm("Are you sure you want to delete ["+s.options[i].text + "]?")
	if(!flag) return
	s.options[i] = null
	while(p>i){
		Palettes[i] = Palettes[i+1]
		i++
	}
	if(30 > s.length) s.size = s.length
}
async function P_Effect(v){
	v=parseInt(v)
	let x = 1
	if(v==255)x=-1
	d = document.paintbbs
	p=String(await d.getColors()).split("\n")
	l = p.length
	var s = ""
	for(n=0;l>n;n++){
		let R = v+(parseInt("0x" + p[n].substring(1,3))*x)
		let G = v+(parseInt("0x" + p[n].substring(3,5))*x)
		let B = v+(parseInt("0x" + p[n].substring(5,7))*x)
		if(R > 255){ R = 255}
		else if(0 > R){ R = 0}
		if(G > 255){ G = 255}
		else if(0 > G){ G = 0}
		if(B > 255){ B = 255}
		else if(0 > B){ B = 0}
		s += "#"+Hex(R)+Hex(G)+Hex(B)+"\n"
	}
	d.setColors(s)
	PaletteListSetColor()
}
async function PaletteMatrixGet(){
	d = document.Palette
	p = Palettes.length
	s = d.select
	m = d.m_m.selectedIndex
	t = d.setr
	switch(m){
	case 0:case 2:default:
	t.value = ""
		n=0;c=0
		while(p>n){
			if(s.options[n] != null){ t.value = t.value + "\n!"+ s.options[n].text +"\n" + Palettes[n];c++}
			n++
		}
		alert ("Number of pallets "+c+"\ngot the palette matrix.");break
	case 1:
	t.value = "!Palette\n"+String(await document.paintbbs.getColors())
		alert("got the palette information currently used.");break
	}
		t.value = t.value.trim() + "\n!Matrix"
}
function PalleteMatrixSet(){
	m = document.Palette.m_m.selectedIndex
	const str = "Set the palette matrix."
	let flag;
	switch(m){
	case 0:default:
		flag = confirm(str+"\nAll current palette information will be lost, is that okay ?");break
	case 1:
		flag = confirm(str+"\nAre you sure you want to replace it with the palette you are currently using?");break;
	case 2:
		flag = confirm(str+"\nAre you sure you want to replace it with the palette you are currently using ?");break
	}
		if (!flag) return
	PaletteSet()
	if(s.length < 30){ s.size = s.length}else{s.size=30}
	if(DynamicColor) PaletteListSetColor()
}
function PalleteMatrixHelp(){
	alert("**ABOUT PALETTE MATRIX**\nThe palette matrix allows you to use free palette settings \nby using text that lists palette information.\n\nGet the matrix\n1)Get the palette matrix from the [Get] button.\n2)The retrieved information will appear in the text area below, copy it all.\n3)Let's save this matrix information as text in a file.\n\nto set matrix\n1)Paste the copied matrix into the text area below.\n2)If you have saved it in a file, copy and paste it.\n3)You can use the saved palette by pressing the set button.\n\nPlease note that the palette will not be set correctly if there is unnecessary information.");
}
function PaletteSet(){
	d = document.Palette
	se = d.setr.value;
	s = d.select;
	m = d.m_m.selectedIndex;
	l = se.length
	let pa;
	if(l<1){
		alert("There is no matrix information.");return
	}
		n = 0;o = 0;e = 0
	switch(m){
	case 0:default:
		n = s.length
		while(n > 0){
			n--
			s.options[n] = null
		}
	case 2:
		i=s.options.length
		n = se.indexOf("!",0)+1
		if(n == 0)return
			let Matrix1 = 1
			let Matrix2 = -1
		while(n<l){
			e = se.indexOf("\n#",n)
			if(e == -1)return
			
			const pn = se.substring(n,e+Matrix1)
			o = se.indexOf("!",e)
			if(o == -1)return
			pa = se.substring(e+1,o+Matrix2)
			if (pn != "Palette"){
			if(i >= 0)s.options[i] = new Option(pn)
			
			Palettes[i] = pa
			i++
			}else{document.paintbbs.setColors(pa)}
			
			n=o+1
		}
		break
	case 1:
		n = se.indexOf("!",0)+1
		if(n == 0)return
		e = se.indexOf("\n#",n)
		o = se.indexOf("!",e)
			if(e >= 0){
				pa = se.substring(e+1,o-1)
			}
		document.paintbbs.setColors(pa)
	}
	PaletteListSetColor()
}
function PaletteListSetColor(){
	var s = document.Palette.select;
	for(i = 1; s.options.length > i; i ++) {
		var c = Palettes[i].split("\n");
		s.options[i].style.background = c[4];
		s.options[i].style.color = GetBright(c[4]);
}
}
function GetBright(c){
	let r=parseInt("0x"+c.substring(1,3));
	let g=parseInt("0x"+c.substring(3,5));
	let b=parseInt("0x"+c.substring(5,7));
	c=(r>=g)?(r>=b)?r:b:(g>=b)?g:b;
	return 128>c?"#FFFFFF":"#000000";
}
function Chenge_(){
	var st = document.grad.pst.value
	var ed = document.grad.ped.value
	
	if(isNaN(parseInt("0x" + st)))return
	if(isNaN(parseInt("0x" + ed)))return
	GradView("#"+st,"#"+ed);
}
function ChengeGrad(){
	var d =document
	var st = d.grad.pst.value
	var ed = d.grad.ped.value
	Chenge_()
	var degi_R = parseInt("0x" + st.substring(0,2))
	var degi_G = parseInt("0x" + st.substring(2,4))
	var degi_B = parseInt("0x" + st.substring(4,6))
	 var R = parseInt((degi_R - parseInt("0x" + ed.substring(0,2)))/15)
	 var G = parseInt((degi_G - parseInt("0x" + ed.substring(2,4)))/15)
	 var B = parseInt((degi_B - parseInt("0x" + ed.substring(4,6)))/15)
	 if(isNaN(R)) R = 1
	 if(isNaN(G)) G = 1
	 if(isNaN(B)) B = 1
	var p = new String()
	let cnt,m1,m2,m3;
	for(cnt=0,m1=degi_R,m2=degi_G,m3=degi_B; 14>cnt; cnt++,m1-=R,m2-=G,m3-=B){
		if((m1 > 255)||(0 > m1)){ R *= -1;m1-=R}
		if((m2 > 255)||(0 > m2)){ G *= -1;m2-=G}
		if((m3 > 255)||(0 > m3)){ B *= -1;m2-=B}
		p += "#"+Hex(m1)+Hex(m2)+Hex(m3)+"\n"
	}
	d.paintbbs.setColors(p);
}
function Hex(n){
	n = parseInt(n);if(0 > n) n *=-1;
	var hex = new String()
	var m
	var k
	while(n > 16){
	m = n
	if(n >16){
		n = parseInt(n/16)
		m -= (n * 16)
	}
		k = Hex_(m)
		hex = k + hex
	}
		k = Hex_(n)
		hex = k + hex
	while(2 > hex.length){hex="0" + hex}
	return hex
}
function Hex_(n){
	if(! isNaN(n)){
		if(n == 10){n="A"}
		else if(n == 11){n="B"}
		else if(n == 12){n="C"}
		else if(n == 13){n="D"}
		else if(n == 14){n="E"}
		else if(n == 15){n="F"}
	}else{n=""}
	return n
}
async function GetPalette(){
	d = document;
	p = String(await d.paintbbs.getColors());
	 if(p == "null" || p == ""){return};
	ps = p.split("\n");
	let st = d.grad.p_st.selectedIndex
	let ed = d.grad.p_ed.selectedIndex
	d.grad.pst.value = ps[st].substring(1,7)
	d.grad.ped.value = ps[ed].substring(1,7)
	GradSelC()
	GradView(ps[st],ps[ed])
	PaletteListSetColor()
}
function GradSelC(){
	if(! d.grad.view.checked)return
	d = document.grad
	l = ps.length
	pe=""
	for(n=0;l>n;n++){
		let R = 255+(parseInt("0x" + ps[n].substring(1,3))*-1)
		let G = 255+(parseInt("0x" + ps[n].substring(3,5))*-1)
		let B = 255+(parseInt("0x" + ps[n].substring(5,7))*-1)
		if(R > 255){ R = 255}
		else if(0 > R){ R = 0}
		if(G > 255){ G = 255}
		else if(0 > G){ G = 0}
		if(B > 255){ B = 255}
		else if(0 > B){ B = 0}
		pe += "#"+Hex(R)+Hex(G)+Hex(B)+"\n"
	}
	pe = pe.split("\n");
	for(n=0;l>n;n++){
		d.p_st.options[n].style.background = ps[n];
		d.p_st.options[n].style.color = pe[n];
		d.p_ed.options[n].style.background = ps[n];
		d.p_ed.options[n].style.color = pe[n];
	}
}
function GradView(st,ed){
	d = document
	if(! d.grad.view.checked)return
}
function showHideLayer() { //v3.0
	d = document
	var l
	if(d.layers) {
 		l = d.layers["psft"]
	}else{
		l = d.getElementById("psft").style;
	}
	if(! d.grad.view.checked){
		l.visibility = "hidden"
	}
	if(d.grad.view.checked){
		l.visibility = "visible";
		GetPalette();
	}
}
