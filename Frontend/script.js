console.log("FUNDBEE Website Loaded");

function welcome(){

alert("Welcome to FUNDBEE");

}
const questions = document.querySelectorAll(".faq-question");

questions.forEach(question => {

question.addEventListener("click", () => {

const faqItem = question.parentElement;

document.querySelectorAll(".faq-item").forEach(item => {
if(item !== faqItem){
item.classList.remove("active");
}
});

faqItem.classList.toggle("active");

});

});

// const cards = document.querySelectorAll(".card");

// cards.forEach(card => {

// card.addEventListener("mousemove",(e)=>{

// const rect = card.getBoundingClientRect();

// const x = e.clientX - rect.left;

// const y = e.clientY - rect.top;

// const centerX = rect.width/2;
// const centerY = rect.height/2;

// const rotateX = -(y-centerY)/15;
// const rotateY = (x-centerX)/15;

// card.style.transform =
// `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05)`;

// });

// card.addEventListener("mouseleave",()=>{

// card.style.transform="rotateX(0) rotateY(0)";

// });

// });
/* 3D cursor tilt */

const cards = document.querySelectorAll(".card");

cards.forEach(card=>{

card.addEventListener("mousemove",(e)=>{

const rect=card.getBoundingClientRect();

const x=e.clientX-rect.left;
const y=e.clientY-rect.top;

const centerX=rect.width/2;
const centerY=rect.height/2;

const rotateX=-(y-centerY)/15;
const rotateY=(x-centerX)/15;

card.style.transform=
perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05);

});

card.addEventListener("mouseleave",()=>{

card.style.transform="rotateX(0) rotateY(0)";

});

});


/* scroll reveal */

function reveal(){

const reveals=document.querySelectorAll(".reveal");

for(let i=0;i<reveals.length;i++){

const windowHeight=window.innerHeight;
const elementTop=reveals[i].getBoundingClientRect().top;

if(elementTop<windowHeight-100){

reveals[i].classList.add("active");

}

}

}

window.addEventListener("scroll",reveal);