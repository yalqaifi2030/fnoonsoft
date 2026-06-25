{{-- Live Arduino sensors: virtual inputs drive PWM LED, servo and buzzer --}}
<div x-data="labSensors()" class="grid gap-5 lg:grid-cols-2">
    {{-- Inputs --}}
    <div class="card-luxury p-6">
        <h3 class="font-cairo text-lg font-black">المستشعرات الافتراضية</h3>
        <p class="mt-1 text-sm text-gray-500">حرّك المنزلقات وراقب المخرجات تتغيّر حيًّا.</p>

        <div class="mt-5 space-y-5" dir="ltr">
            <div>
                <div class="flex justify-between text-xs font-bold text-gray-500"><span>مستشعر الضوء (LDR)</span><span dir="ltr" x-text="light"></span></div>
                <input type="range" min="0" max="1023" x-model.number="light" class="mt-1 w-full">
            </div>
            <div>
                <div class="flex justify-between text-xs font-bold text-gray-500"><span>مستشعر الحرارة (°C)</span><span dir="ltr" x-text="temp + '°'"></span></div>
                <input type="range" min="-10" max="60" x-model.number="temp" class="mt-1 w-full">
            </div>
            <div>
                <div class="flex justify-between text-xs font-bold text-gray-500"><span>مقاومة متغيّرة (Potentiometer)</span><span dir="ltr" x-text="pot"></span></div>
                <input type="range" min="0" max="1023" x-model.number="pot" class="mt-1 w-full">
            </div>
        </div>
    </div>

    {{-- Outputs --}}
    <div class="card-luxury p-6">
        <h3 class="font-cairo text-lg font-black">المخرجات</h3>
        <div class="mt-5 grid grid-cols-3 gap-4 text-center">
            {{-- LED --}}
            <div class="flex flex-col items-center gap-2">
                <div class="flex h-20 w-20 items-center justify-center rounded-full"
                     :style="'background: rgba(250,204,21,' + (0.12 + led / 255 * 0.88) + '); box-shadow: 0 0 ' + (led / 255 * 36) + 'px rgba(250,204,21,.8)'">
                    <i class="fa-solid fa-lightbulb text-2xl" :class="led > 20 ? 'text-amber-600' : 'text-gray-400'"></i>
                </div>
                <span class="text-xs font-bold text-gray-500">LED · <span dir="ltr" x-text="ledPct + '%'"></span></span>
            </div>
            {{-- Servo --}}
            <div class="flex flex-col items-center gap-2">
                <div class="relative flex h-20 w-20 items-end justify-center overflow-hidden rounded-full bg-sky-50">
                    <div class="absolute bottom-1/2 h-7 w-1 origin-bottom rounded bg-sky-600 transition-transform"
                         :style="'transform: rotate(' + (servo - 90) + 'deg)'"></div>
                    <div class="absolute bottom-1/2 h-2 w-2 -mb-1 rounded-full bg-sky-700"></div>
                </div>
                <span class="text-xs font-bold text-gray-500">سيرفو · <span dir="ltr" x-text="servo + '°'"></span></span>
            </div>
            {{-- Buzzer --}}
            <div class="flex flex-col items-center gap-2">
                <div class="flex h-20 w-20 items-center justify-center rounded-full transition"
                     :class="buzzer ? 'bg-rose-500 animate-pulse' : 'bg-gray-100'">
                    <i class="fa-solid fa-volume-high text-2xl" :class="buzzer ? 'text-white' : 'text-gray-400'"></i>
                </div>
                <span class="text-xs font-bold" :class="buzzer ? 'text-rose-600' : 'text-gray-500'">إنذار · <span x-text="buzzer ? 'يعمل' : 'صامت'"></span></span>
            </div>
        </div>

        <div class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm" x-show="night">
            <i class="fa-solid fa-moon text-emerald-600"></i> <span class="font-bold text-emerald-700">وضع ليلي:</span> الإضاءة منخفضة، فتُشعَل الإنارة تلقائيًّا.
        </div>

        {{-- Serial monitor --}}
        <div class="mt-4">
            <div class="mb-1 text-xs font-bold text-gray-500">الشاشة التسلسلية (Serial Monitor)</div>
            <div class="break-all rounded-xl bg-luxury-black p-3 font-mono text-xs text-emerald-300" dir="ltr" x-text="serial"></div>
        </div>
    </div>

    {{-- Equivalent code --}}
    <div class="card-luxury p-6 lg:col-span-2">
        <h3 class="mb-3 font-cairo text-base font-black"><i class="fa-solid fa-code text-saudi-green"></i> كود أردوينو المكافئ</h3>
        <pre class="overflow-x-auto rounded-xl bg-luxury-black p-4 text-xs leading-relaxed text-gray-100" dir="ltr"><code>int led = 9, servoPin = 10, buzzer = 8;

void loop() {
  int light = analogRead(A0);   // <span x-text="light"></span>
  int pot   = analogRead(A1);   // <span x-text="pot"></span>
  float temp = readTemp(A2);    // <span x-text="temp"></span> C

  analogWrite(led, map(pot, 0, 1023, 0, 255));   // PWM = <span x-text="led"></span>
  servo.write(map(pot, 0, 1023, 0, 180));        // <span x-text="servo"></span> deg
  digitalWrite(buzzer, temp > 35 ? HIGH : LOW);  // <span x-text="buzzer ? 'HIGH' : 'LOW'"></span>
}</code></pre>
    </div>
</div>

@push('scripts')
    <script>
        function labSensors() {
            return {
                light: 512, temp: 25, pot: 512,
                get led() { return Math.round(this.pot / 1023 * 255); },
                get ledPct() { return Math.round(this.led / 255 * 100); },
                get servo() { return Math.round(this.pot / 1023 * 180); },
                get buzzer() { return this.temp > 35; },
                get night() { return this.light < 300; },
                get serial() {
                    return 'light=' + this.light + '  temp=' + this.temp + 'C  pot=' + this.pot +
                        '  LED(PWM)=' + this.led + '  servo=' + this.servo + 'deg  buzzer=' + (this.buzzer ? 'ON' : 'off');
                },
            };
        }
    </script>
@endpush
