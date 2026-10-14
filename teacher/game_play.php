<?php
// Man hinh trinh chieu tro choi Ai La Trieu Phu (ban PHP)
// Chuyen the tu html/ai-la-trieu-phu.html — khong sua truc tiep tren thu muc html/.
include '../includes/session_check.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Ai Là Triệu Phú</title>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
<style>
:root{
  --navy:#060b24; --navy2:#0f1a4d; --navy3:#16205c; --navy4:#1c2b78;
  --gold:#ffd875; --gold-deep:#c9971e; --line:#33418f;
  --blue:#2fb6e6; --red:#ff4d5e; --green:#2ecc71; --ink:#f4f6ff;
  padding-top: env(safe-area-inset-top,0px); padding-bottom: env(safe-area-inset-bottom,0px);
  box-sizing:border-box;
}
*{box-sizing:border-box;}
html,body{height:100%;margin:0;}
body{
  background:
    radial-gradient(ellipse 1100px 650px at 50% -8%, rgba(47,182,230,.16), transparent 60%),
    radial-gradient(ellipse 800px 500px at 88% 10%, rgba(255,216,117,.07), transparent 55%),
    radial-gradient(ellipse at 50% -10%, var(--navy3), var(--navy) 62%);
  color:var(--ink); font-family:'Inter',sans-serif; min-height:100%;
  display:flex; overflow-x:hidden;
}
.stage{ display:flex; width:100%; min-height:100vh; }
.ladder{
  display:none; width:clamp(230px,19vw,300px); flex-shrink:0; flex-direction:column; gap:14px;
  padding:20px 16px 20px; border-right:1px solid rgba(255,216,117,.12);
  background:linear-gradient(180deg, rgba(28,43,120,.55), rgba(6,11,36,.35));
  position:sticky; top:0; align-self:flex-start; height:100vh; overflow:hidden;
}
.ladder-head{
  font-family:'Baloo 2',sans-serif; font-weight:800; font-size:15px; color:var(--gold);
  letter-spacing:1.2px; text-align:center; flex-shrink:0; padding-bottom:12px;
  border-bottom:1px solid rgba(255,216,117,.15);
}
.ladder-lifelines{
  display:flex; gap:14px; justify-content:center; padding-bottom:16px;
  border-bottom:1px solid rgba(255,255,255,.08); flex-shrink:0;
}
.ladder-rungs{
  flex:1; min-height:0; overflow-y:auto; overflow-x:hidden; display:flex; flex-direction:column-reverse;
  gap:5px; padding:4px 2px 4px 0; scrollbar-width:thin; scrollbar-color:var(--gold-deep) transparent;
}
.ladder-rungs::-webkit-scrollbar{ width:5px; }
.ladder-rungs::-webkit-scrollbar-thumb{ background:var(--gold-deep); border-radius:4px; }
.rung{
  padding:9px 12px; border-radius:9px; font-weight:700; font-size:15.5px;
  display:flex; align-items:center; justify-content:space-between; gap:8px;
  color:#aab2d8; border:1px solid transparent; flex-shrink:0; transition:transform .15s ease;
}
.rung .rnum{
  width:24px; height:24px; border-radius:50%; background:rgba(255,255,255,.08);
  display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:800; flex-shrink:0;
}
.rung .rprize{ flex:1; text-align:right; }
.rung.safe{ color:var(--gold); font-weight:800; border-color:rgba(242,201,76,.4); background:rgba(242,201,76,.07); }
.rung.safe .rnum{ background:rgba(242,201,76,.2); color:var(--gold); }
.rung.current{
  background:linear-gradient(90deg,var(--gold),#ffe08a); color:var(--navy); transform:scale(1.06);
  box-shadow:0 4px 18px rgba(242,201,76,.5); animation:rungGlow 1.4s ease-in-out infinite; border-color:var(--gold);
}
.rung.current .rnum{ background:rgba(11,20,53,.2); color:var(--navy); }
.rung.done{ color:#4d5690; opacity:.55; }
.rung.done .rprize::after{ content:' ✓'; color:var(--green); }
@keyframes rungGlow{ 0%,100%{ box-shadow:0 4px 18px rgba(242,201,76,.5);} 50%{ box-shadow:0 4px 30px rgba(242,201,76,.9);} }
.main{ flex:1; display:flex; flex-direction:column; align-items:center; padding:24px 20px 40px; gap:20px; min-width:0; }
.brandhead{
  display:flex; flex-direction:column; align-items:center; gap:7px;
  padding-bottom:20px; margin-bottom:4px; width:min(880px,92vw);
  border-bottom:1px solid rgba(255,216,117,.16);
}
.brand{
  font-family:'Baloo 2',sans-serif; font-weight:800; font-size:clamp(22px,3vw,34px); color:var(--gold);
  letter-spacing:.5px; text-align:center; display:flex; align-items:center; gap:10px;
  text-shadow:0 0 20px rgba(255,216,117,.35);
}
.brand-icon{ filter:drop-shadow(0 0 8px rgba(255,216,117,.55)); }
.brand-sub{ font-size:12.5px; letter-spacing:2.5px; color:#93a0d9; font-weight:600; }
.qcard-frame{
  width:min(880px,92vw); border-radius:22px; padding:3px; position:relative;
  background:linear-gradient(135deg,var(--gold-deep),var(--gold) 45%,var(--gold-deep));
  box-shadow:0 14px 40px rgba(0,0,0,.4);
}
.qcard-frame::before,.qcard-frame::after{
  content:''; position:absolute; width:14px; height:14px; background:var(--gold);
  transform:rotate(45deg); box-shadow:0 0 10px rgba(255,216,117,.7);
}
.qcard-frame::before{ top:-7px; left:30px; }
.qcard-frame::after{ bottom:-7px; right:30px; }
.qcard{
  background:linear-gradient(180deg,var(--navy4),var(--navy2) 65%,#0b1442);
  border-radius:20px; padding:30px 34px; text-align:center;
  font-family:'Baloo 2',sans-serif; font-weight:700; font-size:clamp(20px,2.6vw,30px); line-height:1.4;
}
.qcard.hidden-q{ color:#5f6aa0; }
/* Hiển thị công thức, hình học: ảnh/SVG nhúng trong câu hỏi & đáp án */
.qcard img, .qcard svg{ max-width:100%; max-height:340px; width:auto; height:auto; display:block; margin:16px auto 2px; border-radius:10px; background:#fff; padding:6px; box-shadow:0 6px 18px rgba(0,0,0,.3); }
.opt img, .opt svg{ max-width:100%; max-height:160px; width:auto; height:auto; display:block; margin:10px 0 2px; border-radius:8px; background:#fff; padding:4px; }
.qcard .katex-display{ margin:.4em 0; overflow-x:auto; overflow-y:hidden; }
.opt .katex{ font-size:1.05em; }
.qmeta{
  width:min(880px,92vw); display:flex; gap:8px; justify-content:center; flex-wrap:wrap;
  font-family:'Baloo 2',sans-serif; font-weight:700; font-size:14px; letter-spacing:.3px;
}
.qmeta .tag{ background:rgba(255,216,117,.14); border:1px solid rgba(255,216,117,.45); color:var(--gold); border-radius:999px; padding:2px 14px; }
.banner{ width:min(880px,92vw); background:rgba(230,72,79,.15); border:1px solid var(--red); color:#ffd7d9; border-radius:12px; padding:10px 14px; font-size:14px; text-align:left; }
.setup{ width:min(880px,100%); background:linear-gradient(180deg,#141d4d,#0e1538); border:1px solid #33418f; border-radius:18px; padding:22px 26px; display:flex; flex-direction:column; gap:14px; text-align:left; box-shadow:0 12px 40px rgba(0,0,0,.35); }
.setup h3{ font-family:'Baloo 2',sans-serif; font-weight:800; margin:0; color:var(--gold); font-size:20px; }
.cfgrow{ display:flex; gap:14px; flex-wrap:wrap; align-items:flex-start; width:100%; }
.cfgcol{ flex:1; min-width:130px; display:flex; flex-direction:column; gap:4px; }
.cfgcol label{ font-size:12px; color:#aab2d8; font-weight:600; }
.cfgcol.wide{ min-width:100%; }
.cfgcol select,.cfgcol input[type=number]{ background:#0e1538; color:var(--ink); border:2px solid #33418f; border-radius:10px; padding:9px 12px; font-family:'Inter',sans-serif; font-size:14px; width:100%; }
.subjlist{ display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:8px; width:100%; }
.subjlist label{ display:flex; flex-wrap:wrap; align-items:center; gap:4px 8px; background:#1a2560; border:1px solid #33418f; border-radius:10px; padding:8px 12px; cursor:pointer; font-size:14px; color:var(--ink); }
.subjlist label input{ accent-color:var(--gold); width:16px; height:16px; flex-shrink:0; }
.subjlist label .subj-name{ flex:1 1 auto; min-width:0; word-break:break-word; }
.subjlist label .cnt{ flex-basis:100%; font-size:11px; color:#aab2d8; text-align:left; white-space:normal; word-break:break-word; }
.subjlist label.subj-empty{ opacity:.45; pointer-events:none; }
.errmsg{ color:#ffb3b6; font-size:13px; min-height:18px; }
.availmsg{ font-size:13px; color:#c3c9ee; line-height:1.55; }
.availmsg.ok b{ color:var(--green); }
.availmsg.warn b{ color:var(--gold); }
.options{ display:grid; grid-template-columns:1fr 1fr; gap:18px; width:min(880px,92vw); }
.opt{
  background:linear-gradient(135deg,var(--navy4),var(--navy3) 65%); border:2px solid var(--line);
  border-radius:14px; clip-path:polygon(18px 0,100% 0,100% 100%,0 100%,0 18px);
  padding:18px 22px; font-size:clamp(19px,2.4vw,28px); font-weight:700; text-align:left; cursor:pointer;
  display:flex; align-items:center; gap:14px; color:var(--ink); transition:transform .12s ease, border-color .12s ease, box-shadow .12s ease;
}
.opt:hover{ transform:translateY(-3px); border-color:var(--blue); box-shadow:0 8px 20px rgba(47,182,230,.25); }
.opt.eliminated{ opacity:.22; pointer-events:none; }
.opt.selected{ border-color:var(--gold); background:linear-gradient(135deg,#3d3418,#241f0d 65%); }
.opt.correct{ border-color:var(--green); background:linear-gradient(135deg,#123a24,#0c2617 65%); }
.opt.wrong{ border-color:var(--red); background:linear-gradient(135deg,#3a1214,#260c0d 65%); }
.opt b{
  flex-shrink:0; width:38px; height:38px; border-radius:50%; background:linear-gradient(160deg,var(--gold),var(--gold-deep));
  color:var(--navy); display:flex; align-items:center; justify-content:center; font-size:19px;
  font-family:'Baloo 2',sans-serif; box-shadow:0 3px 8px rgba(0,0,0,.3);
}
.opt.correct b{ background:linear-gradient(160deg,#9df5bc,var(--green)); }
.opt.wrong b{ background:linear-gradient(160deg,#ffb0b8,var(--red)); }
.timerwrap{
  position:fixed; top:calc(18px + env(safe-area-inset-top,0px)); right:20px; z-index:30;
  display:flex; align-items:center; gap:10px; flex-direction:column;
}
.timerdisplay{
  width:78px; height:78px; border-radius:50%; border:3px solid var(--blue);
  background:radial-gradient(circle at 30% 30%,var(--navy4),#0b1442); display:flex;
  align-items:center; justify-content:center; font-family:'Baloo 2',sans-serif; font-weight:800;
  font-size:30px; color:var(--blue); box-shadow:0 0 0 4px rgba(47,182,230,.12), 0 6px 16px rgba(0,0,0,.4);
  transition:border-color .2s, color .2s, box-shadow .2s;
}
.timerdisplay.urgent{ border-color:var(--red); color:var(--red); box-shadow:0 0 0 4px rgba(255,77,94,.18), 0 6px 16px rgba(0,0,0,.4); animation:pulse 1s infinite; }
.timerdisplay.timeup{ border-color:var(--red); color:var(--red); background:rgba(230,72,79,.15); animation:timeUpPulse .5s ease-in-out 2; }
@keyframes pulse{ 0%,100%{transform:scale(1);} 50%{transform:scale(1.12);} }
@keyframes timeUpPulse{
  0%,100%{ transform:scale(1); box-shadow:none; }
  50%{ transform:scale(1.16); box-shadow:0 0 0 10px rgba(230,72,79,.22); }
}
.life{
  width:64px; height:64px; border-radius:50%; border:2px solid var(--gold-deep);
  background:radial-gradient(circle at 30% 30%,var(--navy4),#0b1442); display:flex; flex-direction:column;
  align-items:center; justify-content:center; font-weight:800; font-family:'Baloo 2',sans-serif;
  font-size:11px; line-height:1.15; cursor:pointer; color:var(--gold);
  box-shadow:0 4px 10px rgba(0,0,0,.35), 0 0 0 3px rgba(255,216,117,.08);
  transition:transform .15s ease, box-shadow .15s ease;
}
.life .life-icon{ font-size:20px; }
.music-toggle{
  position:fixed; top:calc(16px + env(safe-area-inset-top,0px)); left:16px; z-index:40;
  width:42px; height:42px; border-radius:50%; border:2px solid var(--gold-deep);
  background:radial-gradient(circle at 30% 30%,var(--navy4),#0b1442);
  display:flex; align-items:center; justify-content:center; cursor:pointer;
  font-size:18px; color:var(--gold); box-shadow:0 4px 10px rgba(0,0,0,.35), 0 0 0 3px rgba(255,216,117,.08);
  transition:transform .15s ease, box-shadow .15s ease, color .15s ease, border-color .15s ease;
  user-select:none;
}
.music-toggle:hover{ transform:scale(1.08); }
.music-toggle.muted{ color:#6d76a8; border-color:#33418f; box-shadow:0 4px 10px rgba(0,0,0,.3); }
.life:hover:not(.used){ transform:translateY(-2px) scale(1.08); box-shadow:0 6px 16px rgba(255,216,117,.45), 0 0 0 3px rgba(255,216,117,.15); }
.life.used{ opacity:.2; pointer-events:none; text-decoration:line-through; }
.controls{ display:flex; gap:14px; flex-wrap:wrap; justify-content:center; margin-top:2px; }
button.act{
  background:linear-gradient(160deg,var(--gold),var(--gold-deep)); color:var(--navy); border:none;
  border-radius:10px; padding:13px 26px; font-weight:800; font-family:'Baloo 2',sans-serif; font-size:16px;
  cursor:pointer; box-shadow:0 6px 16px rgba(201,151,30,.35); transition:transform .12s ease, box-shadow .12s ease;
}
button.act:hover:not(:disabled){ transform:translateY(-2px); box-shadow:0 8px 20px rgba(201,151,30,.5); }
button.act.secondary{ background:transparent; color:var(--ink); border:2px solid var(--line); box-shadow:none; }
button.act.secondary:hover:not(:disabled){ border-color:var(--blue); transform:translateY(-2px); }
button.act:disabled{ opacity:.35; cursor:default; transform:none; box-shadow:none; }
.poll{ width:min(880px,92vw); display:flex; flex-direction:column; gap:8px; }
.pollrow{ display:flex; align-items:center; gap:10px; font-size:14px; }
.pollbar{ background:#33418f; height:14px; border-radius:7px; flex:1; overflow:hidden; }
.pollfill{ background:var(--blue); height:100%; width:0%; transition:width .6s ease; }
.startscreen,.endscreen{ text-align:center; display:flex; flex-direction:column; align-items:center; gap:20px; margin-top:8vh; }
.startscreen p, .endscreen p{ color:#c3c9ee; max-width:520px; }
.bigscreen{
  text-align:center; display:flex; flex-direction:column; align-items:center; gap:0;
  width:min(1040px,94vw); margin:0 auto; padding:clamp(22px,3.2vh,46px) clamp(20px,2.6vw,48px);
  background:linear-gradient(180deg,#17225a,#0c1334 68%,#070d24);
  border:3px solid rgba(255,216,117,.5); border-radius:30px; position:relative; overflow:hidden;
  box-shadow:0 0 0 7px rgba(255,216,117,.07), 0 26px 80px rgba(0,0,0,.6);
}
.bigscreen::before{
  content:''; position:absolute; left:-10%; right:-10%; top:-55%; height:130%;
  background:radial-gradient(ellipse at 50% 0%, rgba(255,216,117,.2), transparent 62%); pointer-events:none;
}
.bigscreen > *{ position:relative; }
.bigicon{
  font-size:clamp(32px,7vh,72px); line-height:1; margin-bottom:clamp(6px,1.2vh,12px);
  filter:drop-shadow(0 0 24px rgba(255,216,117,.6)); animation:bob 2.4s ease-in-out infinite;
}
.bigtitle{
  font-family:'Baloo 2',sans-serif; font-weight:800; font-size:clamp(24px,6.6vh,60px); line-height:1.14;
  letter-spacing:.5px; color:var(--gold); text-shadow:0 0 28px rgba(255,216,117,.5), 0 6px 18px rgba(0,0,0,.55);
  margin-bottom:clamp(20px,3.2vh,34px);
  animation:titleGlow 2.4s ease-in-out infinite;
}
.bigtext{
  color:#e4e8ff; font-size:clamp(15px,3vh,23px); line-height:1.55; max-width:820px; font-weight:600;
  margin-bottom:clamp(22px,3.4vh,36px);
}
.bigprize{
  display:flex; flex-direction:column; align-items:center; gap:clamp(2px,0.4vh,5px);
  padding:clamp(12px,2vh,26px) clamp(32px,4.4vw,64px);
  border-radius:24px; border:2px solid rgba(255,216,117,.5);
  background:radial-gradient(ellipse at 50% 0%, rgba(255,216,117,.24), rgba(255,216,117,.05) 72%);
  margin-bottom:clamp(26px,4.1vh,42px);
  animation:prizeGlow 2.8s ease-in-out infinite;
}
.bigprize .lbl{ font-size:clamp(10px,2vh,17px); letter-spacing:3px; text-transform:uppercase; color:#aab5ea; font-weight:700; }
.bigprize .val{
  font-family:'Baloo 2',sans-serif; font-weight:800; font-size:clamp(32px,9.6vh,88px); line-height:1.06;
  color:var(--gold); text-shadow:0 0 32px rgba(255,216,117,.55);
}
button.act.big{
  font-size:clamp(15px,2.7vh,26px); padding:clamp(14px,2.3vh,24px) clamp(30px,4.6vw,56px);
  border-radius:999px; letter-spacing:.5px; margin-top:clamp(5px,0.9vh,9px);
}
#readyscreen, #endscreen{ margin-top:1vh; }
@keyframes bob{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-10px); } }
@keyframes titleGlow{ 0%,100%{ text-shadow:0 0 22px rgba(255,216,117,.42), 0 6px 18px rgba(0,0,0,.55); } 50%{ text-shadow:0 0 40px rgba(255,216,117,.78), 0 6px 18px rgba(0,0,0,.55); } }
@keyframes prizeGlow{ 0%,100%{ box-shadow:0 0 22px rgba(255,216,117,.16); } 50%{ box-shadow:0 0 46px rgba(255,216,117,.42); } }
/* Màn hình thấp (TV 1366x768, máy chiếu): thu gọn header + panel cho vừa trang, không scroll */
@media (max-height:820px){
  .main{ padding:12px 20px 20px; gap:18px; }
  .brandhead{ padding-bottom:20px; margin-bottom:10px; }
  .brand{ font-size:clamp(26px,4.6vh,38px); }
  .brand-sub{ font-size:clamp(11.5px,1.9vh,14px); letter-spacing:2.2px; }
}
@media (max-height:700px){
  .bigicon{ font-size:clamp(24px,5vh,44px); }
  .bigtext{ font-size:clamp(13px,2.5vh,18px); line-height:1.4; }
  .main{ gap:12px; }
  .brandhead{ padding-bottom:12px; margin-bottom:4px; }
  .qcard{ padding:20px 26px; font-size:clamp(17px,2.2vw,25px); }
  .options{ gap:11px; }
  .opt{ padding:11px 14px; }
  .controls{ gap:11px; }
}
@media (max-width:700px){ .bigscreen{ padding:18px 14px; border-radius:22px; } }
@media (max-width:700px){
  .stage{ flex-direction:column; }
  .ladder{ position:static; width:100%; height:auto; flex-direction:row; justify-content:center;
    border-right:none; border-bottom:1px solid rgba(255,255,255,.08); padding:10px 12px; }
  .ladder-head, .ladder-rungs{ display:none; }
  .ladder-lifelines{ border-bottom:none; padding:0; }
  .options{ grid-template-columns:1fr; }
}
@media (max-width:520px){
  .timerwrap{ position:static; margin:0 auto -8px; }
  .brandhead{ padding-bottom:14px; }
}
</style>
</head>
<body>
<audio id="sndDung" preload="auto"><source src="../assets/game/snd_dung.mp3" type="audio/mpeg"></audio>
<audio id="sndSai" preload="auto"><source src="../assets/game/snd_sai.mp3" type="audio/mpeg"></audio>
<audio id="sndNen" preload="auto" loop><source src="../assets/game/snd_nen.mp3" type="audio/mpeg"></audio>
<audio id="sndMoc" preload="auto"><source src="../assets/game/snd_moc.mp3" type="audio/mpeg"></audio>

<div class="music-toggle muted" id="musicToggleBtn" onclick="toggleNen()" title="Bật nhạc nền">🔇</div>
<div class="stage">
  <div class="ladder" id="ladder">
    <div class="ladder-head">💰 Bảng giá trị</div>
    <div class="ladder-lifelines">
      <div class="life" id="life5050" onclick="use5050()" title="50:50 — Loại 2 đáp án sai">
        <span class="life-icon">➗</span><span>50:50</span>
      </div>
      <div class="life" id="lifePoll" onclick="usePoll()" title="Hỏi khán giả">
        <span class="life-icon">🎤</span><span>Khán giả</span>
      </div>
    </div>
    <div class="ladder-rungs" id="ladderRungs"></div>
  </div>
  <div class="main">
    <div class="brandhead">
      <div class="brand"><span class="brand-icon">🏆</span>AI LÀ TRIỆU PHÚ</div>
      <div class="brand-sub">Trò chơi kiến thức học đường</div>
    </div>

    <div id="startscreen" class="startscreen">
      <p>Mời một bạn học sinh lên chơi. Người dẫn chương trình bấm "Bắt đầu chơi" và điều khiển toàn bộ trò chơi ngay trên màn hình chiếu này. Các bạn phía dưới cổ vũ, hò reo hết mình nhé!</p>
      <div class="setup" id="setup">
        <h3>⚙️ Cấu hình trò chơi từ Ngân hàng câu hỏi</h3>
        <div class="cfgrow">
          <div class="cfgcol"><label>KHỐI LỚP</label><select id="cfgGrade"></select></div>
          <div class="cfgcol"><label>HỌC KỲ</label><select id="cfgSemester"></select></div>
        </div>
        <div class="cfgrow">
          <div class="cfgcol wide">
            <label>MÔN HỌC (chọn 1 hoặc nhiều môn — sẽ tự trộn chung)</label>
            <div class="subjlist" id="cfgSubjects"></div>
          </div>
        </div>
        <div class="cfgrow">
          <div class="cfgcol"><label>SỐ CÂU MỨC 1 — DỄ (NB)</label><input type="number" id="cfgNB" min="0" max="30" value="5"></div>
          <div class="cfgcol"><label>SỐ CÂU MỨC 2 — VỪA (TH)</label><input type="number" id="cfgTH" min="0" max="30" value="4"></div>
          <div class="cfgcol"><label>SỐ CÂU MỨC 3 — KHÓ (VD)</label><input type="number" id="cfgVD" min="0" max="30" value="4"></div>
        </div>
        <div class="availmsg" id="availMsg">Đang tải ngân hàng câu hỏi…</div>
        <div class="errmsg" id="startErr"></div>
        <button class="act" id="startBtn" onclick="startGame()">Bắt đầu chơi</button>
      </div>
    </div>

    <div id="readyscreen" class="startscreen" style="display:none;">
      <div class="bigscreen">
        <div class="bigicon">🎬</div>
        <div class="bigtitle">SẴN SÀNG CHƯA?</div>
        <div class="bigtext" id="readyText">Câu hỏi đã sẵn sàng. Khi cả lớp đã ổn định và bạn học sinh đã ngồi vào vị trí, bấm nút bên dưới để bắt đầu câu hỏi đầu tiên!</div>
        <div class="bigprize">
          <div class="lbl">Phần thưởng cao nhất</div>
          <div class="val" id="readyMaxPrize">—</div>
        </div>
        <button class="act big" id="readyBtn" onclick="beginQuestions()">🚀 Bắt đầu câu hỏi đầu tiên</button>
      </div>
    </div>

    <div id="game" style="display:none; width:100%; align-items:center; flex-direction:column; gap:22px;">
      <div class="banner" id="gameBanner" style="display:none;"></div>
      <div class="timerwrap" id="timerwrap">
        <div class="timerdisplay" id="timerDisplay">30</div>
      </div>
      <div class="qmeta" id="qmeta" style="display:none;"></div>
      <div class="qcard-frame"><div class="qcard" id="qcard"></div></div>
      <div class="options" id="options"></div>
      <div class="poll" id="poll" style="display:none;"></div>
      <div class="controls">
        <button class="act" id="revealBtn" onclick="revealAnswer()">Chốt & Xem đáp án</button>
        <button class="act secondary" id="nextBtn" onclick="nextQuestion()" disabled>Câu tiếp theo</button>
        <button class="act secondary" id="stopBtn" onclick="stopHere()">Dừng, giữ điểm</button>
      </div>
    </div>

    <div id="endscreen" class="endscreen" style="display:none;">
      <div class="bigscreen">
        <div class="bigicon" id="endIcon">🎉</div>
        <div class="bigtitle" id="endTitle"></div>
        <div class="bigprize">
          <div class="lbl">Điểm mang về cho lớp</div>
          <div class="val" id="endPrize">—</div>
        </div>
        <div class="bigtext" id="endText"></div>
        <button class="act big" onclick="location.reload()">🔄 Chơi lại từ đầu</button>
      </div>
    </div>
  </div>
</div>

<script>
const DEFAULT_QUESTIONS = [
  {q:"Việt Nam có bao nhiêu tỉnh, thành phố trực thuộc Trung ương hiện nay (trước sáp nhập 2025)?", opts:["58 tỉnh, 5 thành phố","54 tỉnh, 9 thành phố","63 tỉnh, thành phố","50 tỉnh, thành phố"], correct:2},
  {q:"Quốc kỳ Việt Nam có nền màu gì?", opts:["Xanh dương","Đỏ","Vàng","Trắng"], correct:1},
  {q:"1 giờ có bao nhiêu phút?", opts:["100 phút","60 phút","90 phút","120 phút"], correct:1},
  {q:"Thủ đô của Việt Nam là gì?", opts:["TP. Hồ Chí Minh","Đà Nẵng","Hà Nội","Huế"], correct:2},
  {q:"Trong bảng chữ cái tiếng Việt, chữ cái đứng đầu tiên là gì?", opts:["A","B","C","O"], correct:0},
  {q:"Sông dài nhất Việt Nam bắt nguồn từ Trung Quốc, chảy qua nhiều tỉnh miền Bắc, tên là gì?", opts:["Sông Hồng","Sông Mã","Sông Lam","Sông Đà"], correct:0},
  {q:"Bác Hồ đọc bản Tuyên ngôn Độc lập vào ngày tháng năm nào?", opts:["19/8/1945","2/9/1945","30/4/1975","7/5/1954"], correct:1},
  {q:"Hành tinh nào được gọi là 'Hành tinh Đỏ'?", opts:["Kim Tinh","Hoả Tinh","Thổ Tinh","Mộc Tinh"], correct:1},
  {q:"Trong cơ thể người, cơ quan nào có chức năng bơm máu đi khắp cơ thể?", opts:["Phổi","Gan","Tim","Thận"], correct:2},
  {q:"Vịnh nào của Việt Nam được UNESCO công nhận là Di sản Thiên nhiên Thế giới?", opts:["Vịnh Hạ Long","Vịnh Vân Phong","Vịnh Cam Ranh","Vịnh Nha Trang"], correct:0},
  {q:"Ai là tác giả của bài thơ 'Lượm'?", opts:["Xuân Diệu","Tố Hữu","Nguyễn Du","Hàn Mặc Tử"], correct:1},
  {q:"Đơn vị đo cường độ dòng điện là gì?", opts:["Vôn (V)","Oát (W)","Ampe (A)","Ôm (Ω)"], correct:2},
  {q:"Kỳ Thế vận hội Olympic hiện đại đầu tiên được tổ chức tại quốc gia nào?", opts:["Pháp","Hy Lạp","Anh","Ý"], correct:1},
  {q:"Trong Toán học, số Pi (π) xấp xỉ bằng bao nhiêu?", opts:["1.41","2.72","3.14","4.13"], correct:2},
  {q:"Danh hiệu 'Anh hùng Lao động' và câu hỏi khó nhất: Việt Nam gia nhập Liên Hợp Quốc vào năm nào?", opts:["1975","1977","1986","1995"], correct:1}
];

const LEVEL_LABELS = { NB: 'Nhận biết', TH: 'Thông hiểu', VD: 'Vận dụng' };
const LEVEL_DOTS = { NB: '🟢', TH: '🟡', VD: '🔴' };
const API = '../api/game_questions.php';

let QUESTIONS = [], PRIZES = [], SAFE = [], gameMeta = null, sourceLabel = 'ngân hàng câu hỏi';

let current = 0, eliminated = [], answered = false, used5050 = false, usedPoll = false, selectedIdx = null;
const TIME_LIMIT = 30;
let timeLeft = TIME_LIMIT, timerInterval = null, actx = null, heartbeatInterval = null;

function ensureAudio(){
  if(!actx){
    try{ actx = new (window.AudioContext||window.webkitAudioContext)(); }catch(e){}
  }
}

// ===== Âm thanh thu sẵn (mp3): Đúng / Sai / Nhạc nền / Đạt mốc quan trọng =====
function playClip(id, volume){
  const el = document.getElementById(id);
  if(!el) return;
  try{
    el.volume = (typeof volume === 'number') ? volume : 1;
    el.currentTime = 0;
    el.play().catch(()=>{});
  }catch(e){}
}
function playDungSound(){ playClip('sndDung'); }
function playSaiSound(){ playClip('sndSai'); }
function playMocSound(){ playClip('sndMoc'); }

let nenPlaying = false;
function updateMusicBtn(){
  const btn = document.getElementById('musicToggleBtn');
  if(!btn) return;
  btn.textContent = nenPlaying ? '🔊' : '🔇';
  btn.classList.toggle('muted', !nenPlaying);
  btn.title = nenPlaying ? 'Tắt nhạc nền' : 'Bật nhạc nền';
}
function startNenSound(){
  const el = document.getElementById('sndNen');
  if(!el) return;
  try{
    el.volume = 0.35;
    el.currentTime = 0;
    el.play().then(()=>{ nenPlaying = true; updateMusicBtn(); }).catch(()=>{ nenPlaying = false; updateMusicBtn(); });
  }catch(e){}
}
function stopNenSound(){
  const el = document.getElementById('sndNen');
  if(!el) return;
  try{ el.pause(); el.currentTime = 0; }catch(e){}
  nenPlaying = false;
  updateMusicBtn();
}
function toggleNen(){
  const el = document.getElementById('sndNen');
  if(!el) return;
  if(el.paused){
    try{
      el.volume = 0.35;
      el.play().then(()=>{ nenPlaying = true; updateMusicBtn(); }).catch(()=>{});
    }catch(e){}
  } else {
    el.pause();
    nenPlaying = false;
    updateMusicBtn();
  }
}
function playTick(urgent){
  if(!actx) return;
  const t = actx.currentTime;
  const osc = actx.createOscillator(), gain = actx.createGain();
  osc.type = 'square';
  osc.frequency.value = urgent ? 1300 : 850;
  gain.gain.setValueAtTime(0.16, t);
  gain.gain.exponentialRampToValueAtTime(0.001, t+0.09);
  osc.connect(gain); gain.connect(actx.destination);
  osc.start(t); osc.stop(t+0.1);
}
// ===== Tiếng nhịp tim hồi hộp trong lúc suy nghĩ, dồn dập hơn qua các mốc 5/10/15 =====
function playHeartbeatThump(volume){
  if(!actx) return;
  const t = actx.currentTime;
  const o1 = actx.createOscillator(), g1 = actx.createGain();
  o1.type = 'sine';
  o1.frequency.setValueAtTime(90, t);
  o1.frequency.exponentialRampToValueAtTime(45, t+0.12);
  g1.gain.setValueAtTime(volume, t);
  g1.gain.exponentialRampToValueAtTime(0.001, t+0.14);
  o1.connect(g1); g1.connect(actx.destination);
  o1.start(t); o1.stop(t+0.15);
  const t2 = t + 0.18;
  const o2 = actx.createOscillator(), g2 = actx.createGain();
  o2.type = 'sine';
  o2.frequency.setValueAtTime(75, t2);
  o2.frequency.exponentialRampToValueAtTime(38, t2+0.11);
  g2.gain.setValueAtTime(volume*0.7, t2);
  g2.gain.exponentialRampToValueAtTime(0.001, t2+0.13);
  o2.connect(g2); g2.connect(actx.destination);
  o2.start(t2); o2.stop(t2+0.14);
}
function tensionLevel(){
  const qNum = current + 1;
  if(qNum >= 15) return 4;
  if(qNum >= 10) return 3;
  if(qNum >= 5) return 2;
  return 1;
}
function startHeartbeat(){
  stopHeartbeat();
  ensureAudio();
  if(!actx) return;
  const level = tensionLevel();
  const interval = [null,1100,900,700,520][level];
  const volume = 0.05 + level*0.02;
  playHeartbeatThump(volume);
  heartbeatInterval = setInterval(()=>playHeartbeatThump(volume), interval);
}
function stopHeartbeat(){
  if(heartbeatInterval){ clearInterval(heartbeatInterval); heartbeatInterval = null; }
}

// ===== Tiếng "tinh" khi chọn / chốt đáp án =====
function playSelectClick(){
  ensureAudio();
  if(!actx) return;
  const t = actx.currentTime;
  const osc = actx.createOscillator(), gain = actx.createGain();
  osc.type = 'sine'; osc.frequency.setValueAtTime(1200, t);
  gain.gain.setValueAtTime(0.12, t);
  gain.gain.exponentialRampToValueAtTime(0.001, t+0.08);
  osc.connect(gain); gain.connect(actx.destination);
  osc.start(t); osc.stop(t+0.09);
}
function playLockConfirm(){
  ensureAudio();
  if(!actx) return;
  const t = actx.currentTime;
  [1400,1900].forEach((f,i)=>{
    const start = t + i*0.09;
    const osc = actx.createOscillator(), gain = actx.createGain();
    osc.type = 'sine'; osc.frequency.value = f;
    gain.gain.setValueAtTime(0.16, start);
    gain.gain.exponentialRampToValueAtTime(0.001, start+0.15);
    osc.connect(gain); gain.connect(actx.destination);
    osc.start(start); osc.stop(start+0.16);
  });
}

// ===== Âm thanh dùng quyền trợ giúp: 50:50 và Hỏi khán giả =====
function play5050Sound(){
  ensureAudio();
  if(!actx) return;
  const t = actx.currentTime;
  [700,500].forEach((f,i)=>{
    const start = t + i*0.15;
    const osc = actx.createOscillator(), gain = actx.createGain();
    osc.type = 'sawtooth';
    osc.frequency.setValueAtTime(f, start);
    osc.frequency.exponentialRampToValueAtTime(f*0.5, start+0.18);
    gain.gain.setValueAtTime(0.14, start);
    gain.gain.exponentialRampToValueAtTime(0.001, start+0.2);
    osc.connect(gain); gain.connect(actx.destination);
    osc.start(start); osc.stop(start+0.22);
  });
}
function playAudienceSound(){
  ensureAudio();
  if(!actx) return;
  const t = actx.currentTime;
  const duration = 1.2;
  const bufferSize = Math.floor(actx.sampleRate*duration);
  const buffer = actx.createBuffer(1, bufferSize, actx.sampleRate);
  const data = buffer.getChannelData(0);
  for(let i=0;i<bufferSize;i++){ data[i] = (Math.random()*2-1)*0.6; }
  const noise = actx.createBufferSource();
  noise.buffer = buffer;
  const filter = actx.createBiquadFilter();
  filter.type = 'bandpass'; filter.frequency.value = 700; filter.Q.value = 0.7;
  const gain = actx.createGain();
  gain.gain.setValueAtTime(0.0001, t);
  gain.gain.linearRampToValueAtTime(0.12, t+0.4);
  gain.gain.linearRampToValueAtTime(0.08, t+duration*0.7);
  gain.gain.exponentialRampToValueAtTime(0.0001, t+duration);
  noise.connect(filter); filter.connect(gain); gain.connect(actx.destination);
  noise.start(t); noise.stop(t+duration);
  [900,1100,1300].forEach((f,i)=>{
    const start = t + 0.3 + i*0.25;
    const osc = actx.createOscillator(), g = actx.createGain();
    osc.type = 'sine'; osc.frequency.value = f;
    g.gain.setValueAtTime(0.08, start);
    g.gain.exponentialRampToValueAtTime(0.001, start+0.18);
    osc.connect(g); g.connect(actx.destination);
    osc.start(start); osc.stop(start+0.2);
  });
}

function playSuspense(callback){
  ensureAudio();
  if(!actx){ callback(); return; }
  const t = actx.currentTime;
  const osc = actx.createOscillator(), gain = actx.createGain();
  osc.type = 'sawtooth';
  osc.frequency.setValueAtTime(80, t);
  osc.frequency.exponentialRampToValueAtTime(420, t+1.4);
  gain.gain.setValueAtTime(0.0001, t);
  gain.gain.exponentialRampToValueAtTime(0.13, t+0.3);
  gain.gain.exponentialRampToValueAtTime(0.0001, t+1.4);
  osc.connect(gain); gain.connect(actx.destination);
  osc.start(t); osc.stop(t+1.45);
  setTimeout(callback, 1400);
}
function resetTimer(){
  clearInterval(timerInterval);
  timeLeft = TIME_LIMIT;
  const el = document.getElementById('timerDisplay');
  el.textContent = timeLeft;
  el.classList.remove('urgent','timeup');
}
function startTimer(){
  ensureAudio();
  clearInterval(timerInterval);
  timerInterval = setInterval(()=>{
    timeLeft--;
    const el = document.getElementById('timerDisplay');
    el.textContent = Math.max(timeLeft,0);
    if(timeLeft<=10 && timeLeft>0){
      el.classList.add('urgent');
      playTick(timeLeft<=5);
    }
    if(timeLeft<=0){
      clearInterval(timerInterval);
      el.classList.remove('urgent');
      el.classList.add('timeup');
    }
  },1000);
}

function buildLadder(){
  const el = document.getElementById('ladderRungs');
  el.innerHTML = '';
  PRIZES.forEach((p,i)=>{
    const d = document.createElement('div');
    d.className = 'rung' + (SAFE.includes(i)?' safe':'') + (i===current?' current':'') + (i<current?' done':'');
    d.innerHTML = `<span class="rnum">${i+1}</span><span class="rprize">${p}</span>`;
    el.appendChild(d);
  });
  const cur = el.querySelector('.rung.current');
  if(cur) cur.scrollIntoView({block:'center', behavior:'smooth'});
}

function qs(name){ return new URLSearchParams(location.search).get(name); }
function clampInt(v, d){ const n = parseInt(v, 10); return Number.isFinite(n) ? Math.max(0, Math.min(30, n)) : d; }
function esc(s){ return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

// Giữ thẻ HTML an toàn (img/svg/br/sub/sup…) trong câu hỏi để hiển thị
// công thức, hình học; loại bỏ script và thuộc tính nguy hiểm.
function sanitizeHtml(html){
  const box = document.createElement('div');
  box.innerHTML = String(html == null ? '' : html);
  box.querySelectorAll('script,style,iframe,object,embed,link,meta,form,input,button,textarea,select,video,audio').forEach(n => n.remove());
  box.querySelectorAll('*').forEach(node => {
    [...node.attributes].forEach(attr => {
      const name = attr.name.toLowerCase();
      const val = String(attr.value || '');
      if (name.startsWith('on')) { node.removeAttribute(attr.name); return; }
      if ((name === 'href' || name === 'src' || name === 'xlink:href') && /^\s*javascript:/i.test(val)) {
        node.removeAttribute(attr.name);
      }
    });
  });
  return box.innerHTML;
}

const MATH_OPTIONS = {
  delimiters: [
    {left: '$$', right: '$$', display: true},
    {left: '$', right: '$', display: false},
    {left: '\\(', right: '\\)', display: false},
    {left: '\\[', right: '\\]', display: true}
  ],
  throwOnError: false,
  ignoredTags: ['script','noscript','style','textarea','pre','code','option']
};
const mathPending = new Set();

function renderMath(el){
  if (!el) return;
  if (typeof renderMathInElement === 'function') {
    try { renderMathInElement(el, MATH_OPTIONS); } catch(e){}
  } else {
    mathPending.add(el);
  }
}
window.addEventListener('load', function(){
  if (typeof renderMathInElement === 'function') {
    mathPending.forEach(el => { try { renderMathInElement(el, MATH_OPTIONS); } catch(e){} });
    mathPending.clear();
  }
});

let metaData = null, selectedSubjects = [];

async function loadMeta(){
  const el = document.getElementById('availMsg');
  const g = document.getElementById('cfgGrade').value;
  const s = document.getElementById('cfgSemester').value;
  el.className = 'availmsg';
  el.textContent = 'Đang tải ngân hàng câu hỏi…';
  try {
    const r = await fetch(API + '?action=meta&grade=' + encodeURIComponent(g) + '&semester=' + encodeURIComponent(s), {cache:'no-store'});
    const d = await r.json();
    if (!d.success) throw new Error(d.message || 'Không tải được dữ liệu ngân hàng câu hỏi.');
    metaData = d;
    const sSel = document.getElementById('cfgSemester');
    if (!sSel.value) sSel.value = d.currentSemester || 'hk1';
    renderSubjects();
    renderAvail();
  } catch(e) {
    el.textContent = '⚠ ' + (e.message || 'Không kết nối được máy chủ.');
  }
}

function renderSubjects(){
  const box = document.getElementById('cfgSubjects');
  box.innerHTML = '';
  metaData.subjects.forEach(sub => {
    const hasQ = sub.total > 0;
    const label = document.createElement('label');
    label.className = hasQ ? '' : 'subj-empty';
    const cnt = hasQ
      ? 'NB ' + sub.levels.NB + ' · TH ' + sub.levels.TH + ' · VD ' + sub.levels.VD + ' · ' + sub.total + ' câu'
      : 'chưa có câu hỏi';
    label.innerHTML = '<input type="checkbox" value="' + sub.id + '"><span class="subj-name">' + esc(sub.name) + '</span><span class="cnt">' + cnt + '</span>';
    if (hasQ) {
      if (selectedSubjects.includes(sub.id)) label.querySelector('input').checked = true;
      label.querySelector('input').addEventListener('change', (e) => {
        const id = sub.id;
        if (e.target.checked) { if(!selectedSubjects.includes(id)) selectedSubjects.push(id); }
        else { selectedSubjects = selectedSubjects.filter(x => x !== id); }
        renderAvail();
      });
    }
    box.appendChild(label);
  });
}

function collectCounts(){
  return {
    NB: clampInt(document.getElementById('cfgNB').value, 5),
    TH: clampInt(document.getElementById('cfgTH').value, 4),
    VD: clampInt(document.getElementById('cfgVD').value, 4)
  };
}

function renderAvail(){
  const el = document.getElementById('availMsg');
  if (!metaData || !selectedSubjects.length) {
    el.className = 'availmsg';
    el.textContent = selectedSubjects.length ? '' : 'Chọn ít nhất một môn học ở trên, chọn số câu cho mỗi mức độ, rồi bấm "Bắt đầu chơi".';
    return;
  }
  const sum = {NB:0, TH:0, VD:0};
  metaData.subjects.forEach(s => {
    if (selectedSubjects.includes(s.id)) {
      sum.NB += s.levels.NB; sum.TH += s.levels.TH; sum.VD += s.levels.VD;
    }
  });
  const counts = collectCounts();
  let html = '', warn = false;
  [['NB','Mức 1 (Dễ)'],['TH','Mức 2 (Vừa)'],['VD','Mức 3 (Khó)']].forEach(([k, label]) => {
    const ok = counts[k] <= sum[k] && sum[k] > 0;
    if (counts[k] > 0 && !ok) warn = true;
    html += '<div>' + label + ': cần <b>' + counts[k] + '</b>, ngân hàng có <b>' + sum[k] + '</b> — '
      + (counts[k] > 0 && !ok ? '<span style="color:#ffb3b6">sẽ tự bù từ mức dễ hơn</span>' : ok ? '<span class="ok">sẵn sàng</span>' : '<span style="color:#ffb3b6">không đủ, giảm bớt</span>') + '</div>';
  });
  const total = counts.NB + counts.TH + counts.VD;
  html += '<div>Tổng trò chơi: <b>' + total + '</b> câu hỏi.</div>';
  el.className = warn ? 'availmsg warn' : 'availmsg ok';
  el.innerHTML = html;
}

function initConfig(){
  const gSel = document.getElementById('cfgGrade'), sSel = document.getElementById('cfgSemester');
  const grades = ['khoi6','khoi7','khoi8','khoi9'];
  const labels = {khoi6:'Khối 6', khoi7:'Khối 7', khoi8:'Khối 8', khoi9:'Khối 9'};
  grades.forEach(g => { const o = document.createElement('option'); o.value = g; o.textContent = labels[g]; gSel.add(o); });
  const gradeParam = qs('grade');
  gSel.value = grades.includes(gradeParam) ? gradeParam : 'khoi7';
  ['hk1','hk2'].forEach(h => { const o = document.createElement('option'); o.value = h; o.textContent = h === 'hk1' ? 'Học kì 1' : 'Học kì 2'; sSel.add(o); });
  sSel.value = qs('semester') || '';
  const subsParam = qs('subjects');
  if (subsParam) selectedSubjects = subsParam.split(',').map(Number).filter(n => n > 0);
  document.getElementById('cfgNB').value = clampInt(qs('nb'), 5);
  document.getElementById('cfgTH').value = clampInt(qs('th'), 4);
  document.getElementById('cfgVD').value = clampInt(qs('vd'), 4);
  gSel.addEventListener('change', loadMeta);
  sSel.addEventListener('change', loadMeta);
  ['cfgNB','cfgTH','cfgVD'].forEach(id => document.getElementById(id).addEventListener('input', renderAvail));
  loadMeta();
}

function buildQuestionsParams(){
  const p = new URLSearchParams();
  p.set('action', 'questions');
  p.set('grade', document.getElementById('cfgGrade').value);
  p.set('semester', document.getElementById('cfgSemester').value);
  const c = collectCounts();
  p.set('nb', c.NB); p.set('th', c.TH); p.set('vd', c.VD);
  selectedSubjects.forEach(id => p.append('subjects[]', id));
  return p;
}

async function fetchQuestions(){
  if (!selectedSubjects.length) throw new Error('Vui lòng chọn ít nhất một môn học.');
  try {
    const r = await fetch(API + '?' + buildQuestionsParams().toString(), {cache:'no-store'});
    if (!r.ok) {
      if (r.status === 401) throw new Error('Vui lòng đăng nhập tài khoản giáo viên trên hệ thống rồi mở lại trò chơi.');
      const d = await r.json().catch(() => null);
      throw new Error((d && d.message) || ('Lỗi máy chủ (' + r.status + ')'));
    }
    const d = await r.json();
    if (!d.success) throw new Error(d.message || 'Không tải được câu hỏi.');
    gameMeta = d.metadata || null;
    return d.questions.map(q => ({ question: q.question, opts: q.options, correct: q.correct, level: q.level || 'NB', subject: q.subject || '' }));
  } catch(err) {
    if (err instanceof TypeError) {
      sourceLabel = 'bộ câu hỏi mặc định (không kết nối được máy chủ)';
      return DEFAULT_QUESTIONS.map((q, i) => ({
        question: q.q, opts: q.opts, correct: q.correct,
        level: i < 5 ? 'NB' : i < 9 ? 'TH' : 'VD'
      }));
    }
    throw err;
  }
}

function buildPrizes(n){
  const base = [10,20,30,50,70,100,150,200,300,500,700,1000,2000,5000,10000];
  const out = [];
  for (let i = 0; i < n; i++) {
    out.push(i < base.length ? base[i] : base[base.length - 1] * Math.pow(2, i - base.length + 1));
  }
  return out.map(v => v >= 1000 ? v.toLocaleString('vi-VN') + ' điểm' : v + ' điểm');
}

function computeSafe(list){
  const lastOf = lv => { for (let i = list.length - 1; i >= 0; i--) { if ((list[i].level || 'NB') === lv) return i; } return -1; };
  const safe = [], last = list.length - 1;
  const nb = lastOf('NB'), th = lastOf('TH');
  if (nb > 0) safe.push(nb);
  if (th > nb) safe.push(th);
  if (!safe.includes(last)) safe.push(last);
  return safe.sort((a, b) => a - b);
}

function prepareGame(list){
  QUESTIONS = list;
  PRIZES = buildPrizes(list.length);
  SAFE = computeSafe(list);
  const banner = document.getElementById('gameBanner');
  banner.innerHTML = '';
  const msgs = [];
  if (sourceLabel !== 'ngân hàng câu hỏi') msgs.push(sourceLabel);
  if (gameMeta) {
    (gameMeta.warnings || []).forEach(w => msgs.push(w));
  }
  if (msgs.length) {
    banner.style.display = 'block';
    msgs.forEach(m => { const d = document.createElement('div'); d.textContent = '⚠ ' + m; banner.appendChild(d); });
  } else {
    banner.style.display = 'none';
  }
}

function startGame(){
  const btn = document.getElementById('startBtn');
  const err = document.getElementById('startErr');
  btn.disabled = true; btn.textContent = 'Đang tải câu hỏi…'; err.textContent = '';
  startNenSound();
  fetchQuestions().then(list => {
    if (!list.length) throw new Error('Không có câu hỏi phù hợp với cấu hình đã chọn.');
    prepareGame(list);
    document.getElementById('startscreen').style.display = 'none';
    document.getElementById('readyscreen').style.display = 'flex';
    document.getElementById('readyMaxPrize').textContent = PRIZES[PRIZES.length-1];
    const subjects = [...new Set(list.map(q => q.subject).filter(Boolean))];
    document.getElementById('readyText').textContent =
      `Tổng cộng ${list.length} câu hỏi`
      + (subjects.length ? ` · Môn: ${subjects.join(', ')}` : '')
      + '. Khi cả lớp đã ổn định và bạn học sinh đã sẵn sàng, bấm nút bên dưới để bắt đầu!';
  }).catch(e => {
    btn.disabled = false; btn.textContent = 'Bắt đầu chơi';
    err.textContent = e.message || 'Không tải được câu hỏi. Vui lòng kiểm tra kết nối máy chủ.';
  });
}

function beginQuestions(){
  document.getElementById('readyscreen').style.display = 'none';
  document.getElementById('game').style.display = 'flex';
  document.getElementById('ladder').style.display = 'flex';
  loadQuestion();
}

function loadQuestion(){
  answered = false; selectedIdx = null; eliminated = [];
  document.getElementById('poll').style.display = 'none';
  document.getElementById('poll').innerHTML = '';
  document.getElementById('nextBtn').disabled = true;
  document.getElementById('revealBtn').disabled = false;
  document.getElementById('revealBtn').textContent = 'Chốt & Xem đáp án';
  document.getElementById('stopBtn').style.display = '';
  resetTimer();
  startTimer();
  startHeartbeat();
  buildLadder();
  const q = QUESTIONS[current];
  const qcardEl = document.getElementById('qcard');
  qcardEl.innerHTML = sanitizeHtml(q.question);
  renderMath(qcardEl);
  const qmeta = document.getElementById('qmeta');
  const tags = [];
  if (q.subject) tags.push(q.subject);
  if (q.level) tags.push((LEVEL_LABELS[q.level] || q.level) + ' ' + (LEVEL_DOTS[q.level] || ''));
  qmeta.innerHTML = tags.map(t => '<span class="tag">' + esc(t) + '</span>').join('');
  qmeta.style.display = tags.length ? 'flex' : 'none';
  const optDiv = document.getElementById('options');
  optDiv.innerHTML = '';
  const letters = ['A','B','C','D'];
  q.opts.forEach((opt,i)=>{
    const b = document.createElement('div');
    b.className = 'opt';
    b.id = 'opt'+i;
    b.innerHTML = `<b>${letters[i]}.</b> <span>${sanitizeHtml(opt)}</span>`;
    b.onclick = ()=>selectOption(i);
    optDiv.appendChild(b);
  });
  renderMath(optDiv);
}

function selectOption(i){
  if(answered || eliminated.includes(i)) return;
  playSelectClick();
  selectedIdx = i;
  for(let j=0;j<4;j++){
    document.getElementById('opt'+j).classList.remove('selected');
  }
  document.getElementById('opt'+i).classList.add('selected');
}

function revealAnswer(){
  if(answered) return;
  answered = true;
  clearInterval(timerInterval);
  stopHeartbeat();
  playLockConfirm();
  document.getElementById('revealBtn').disabled = true;
  playSuspense(()=>{
    const q = QUESTIONS[current];
    for(let i=0;i<4;i++){
      const el = document.getElementById('opt'+i);
      if(i===q.correct) el.classList.add('correct');
      else if(i===selectedIdx) el.classList.add('wrong');
    }
    const correct = selectedIdx===q.correct;
    if(correct){
      if(SAFE.includes(current)) playMocSound(); else playDungSound();
    } else {
      playSaiSound();
    }
    document.getElementById('nextBtn').disabled = false;
    if(!correct){
      document.getElementById('stopBtn').style.display = 'none';
      document.getElementById('nextBtn').textContent = 'Xem kết quả';
      document.getElementById('nextBtn').onclick = ()=>endGame(false);
    } else if(current===QUESTIONS.length-1){
      document.getElementById('nextBtn').textContent = 'Chiến thắng!';
      document.getElementById('nextBtn').onclick = ()=>endGame(true);
    } else {
      document.getElementById('nextBtn').textContent = 'Câu tiếp theo';
      document.getElementById('nextBtn').onclick = nextQuestion;
    }
  });
}

function nextQuestion(){
  current++;
  loadQuestion();
}

function stopHere(){
  if(answered && selectedIdx!==QUESTIONS[current].correct) return;
  stopHeartbeat();
  stopNenSound();
  let lastSafe = -1;
  for(const s of SAFE){ if(s <= current-1) lastSafe = s; }
  const wonIdx = answered && selectedIdx===QUESTIONS[current].correct ? current : (current-1>=0?current-1:-1);
  const prize = wonIdx>=0 ? PRIZES[wonIdx] : "0 điểm";
  document.getElementById('game').style.display = 'none';
  document.getElementById('ladder').style.display = 'none';
  document.getElementById('endscreen').style.display = 'flex';
  document.getElementById('endIcon').textContent = '🎉';
  document.getElementById('endTitle').textContent = 'DỪNG LẠI VÀ GIỮ ĐIỂM!';
  document.getElementById('endPrize').textContent = prize;
  document.getElementById('endText').textContent = `Bạn học sinh mang về ${prize} cho lớp mình. Vỗ tay chúc mừng nào!`;
}

function endGame(win){
  stopHeartbeat();
  stopNenSound();
  document.getElementById('game').style.display = 'none';
  document.getElementById('ladder').style.display = 'none';
  document.getElementById('endscreen').style.display = 'flex';
  if(win){
    document.getElementById('endIcon').textContent = '🏆';
    document.getElementById('endTitle').textContent = 'XUẤT SẮC — TRIỆU PHÚ TRI THỨC!';
    document.getElementById('endPrize').textContent = PRIZES[QUESTIONS.length-1];
    document.getElementById('endText').textContent = `Trả lời đúng toàn bộ ${QUESTIONS.length} câu hỏi, giành ${PRIZES[QUESTIONS.length-1]}! Cả trường cùng vỗ tay thật lớn nào!`;
  } else {
    let lastSafe = -1;
    for(const s of SAFE){ if(s < current) lastSafe = s; }
    const prize = lastSafe>=0 ? PRIZES[lastSafe] : "0 điểm";
    document.getElementById('endIcon').textContent = '💫';
    document.getElementById('endTitle').textContent = 'RẤT TIẾC, TRẢ LỜI CHƯA ĐÚNG!';
    document.getElementById('endPrize').textContent = prize;
    document.getElementById('endText').textContent = `Nhờ mốc an toàn, bạn vẫn mang về ${prize}. Cảm ơn bạn đã tham gia, cả trường cho một tràng pháo tay!`;
  }
}

function use5050(){
  if(used5050 || answered) return;
  play5050Sound();
  used5050 = true;
  document.getElementById('life5050').classList.add('used');
  const q = QUESTIONS[current];
  const wrongIdx = [0,1,2,3].filter(i=>i!==q.correct);
  wrongIdx.sort(()=>Math.random()-0.5);
  const toRemove = wrongIdx.slice(0,2);
  toRemove.forEach(i=>{
    eliminated.push(i);
    document.getElementById('opt'+i).classList.add('eliminated');
  });
}

function usePoll(){
  if(usedPoll || answered) return;
  playAudienceSound();
  usedPoll = true;
  document.getElementById('lifePoll').classList.add('used');
  const q = QUESTIONS[current];
  const pollDiv = document.getElementById('poll');
  pollDiv.style.display = 'flex';
  pollDiv.innerHTML = '';
  let vals = [0,1,2,3].map(i=> eliminated.includes(i) ? 0 : Math.random());
  vals[q.correct] += 1.5;
  const sum = vals.reduce((a,b)=>a+b,0);
  const letters = ['A','B','C','D'];
  vals.forEach((v,i)=>{
    const pct = Math.round(v/sum*100);
    const row = document.createElement('div');
    row.className = 'pollrow';
    row.innerHTML = `<span>${letters[i]}</span><div class="pollbar"><div class="pollfill" id="fill${i}"></div></div><span>${pct}%</span>`;
    pollDiv.appendChild(row);
  });
  setTimeout(()=>{
    vals.forEach((v,i)=>{
      const pct = Math.round(v/sum*100);
      const fillEl = document.getElementById('fill'+i);
      if(fillEl) fillEl.style.width = pct+'%';
    });
  }, 50);
}

initConfig();
</script>
</body>
</html>
