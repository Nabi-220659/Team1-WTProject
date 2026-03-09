const loanAmount=document.getElementById("loanAmount");
const interestRate=document.getElementById("interestRate");
const loanTenure=document.getElementById("loanTenure");

const loanValue=document.getElementById("loanValue");
const rateValue=document.getElementById("rateValue");
const tenureValue=document.getElementById("tenureValue");

const emiValue=document.getElementById("emiValue");
const interestValue=document.getElementById("interestValue");
const totalValue=document.getElementById("totalValue");

const ctx=document.getElementById("emiChart");

let chart;

function calculate(){

let P=loanAmount.value;

let r=interestRate.value/12/100;

let n=loanTenure.value*12;

let emi=(P*r*Math.pow(1+r,n))/(Math.pow(1+r,n)-1);

let total=emi*n;

let interest=total-P;

loanValue.innerText=Number(P).toLocaleString();

rateValue.innerText=interestRate.value;

tenureValue.innerText=loanTenure.value;

emiValue.innerText=Math.round(emi).toLocaleString();

interestValue.innerText=Math.round(interest).toLocaleString();

totalValue.innerText=Math.round(total).toLocaleString();

updateChart(P,interest);

}

function updateChart(principal,interest){

if(chart) chart.destroy();

chart=new Chart(ctx,{

type:'doughnut',

data:{

labels:['Principal','Interest'],

datasets:[{

data:[principal,interest],

backgroundColor:['#356dff','#ff7b2c'],

borderWidth:0

}]

},

options:{

cutout:'70%',

plugins:{

legend:{display:true}

}

}

});

}

loanAmount.addEventListener("input",calculate);

interestRate.addEventListener("input",calculate);

loanTenure.addEventListener("input",calculate);

loanAmount.value=2000000;

interestRate.value=10;

loanTenure.value=10;

calculate();