<template>
  <div class="landing-page">
    <!-- Hero Section -->
    <section class="relative pt-20 pb-32 overflow-hidden">
      <div class="container mx-auto px-4 md:px-6 relative z-10">
        <div class="text-center max-w-4xl mx-auto">
          <div class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium text-primary-600 bg-primary-50 border border-primary-100 mb-8">
            <span class="flex h-2 w-2 rounded-full bg-primary-600 mr-2"></span>
            New: AI-Powered Expense Tracking
          </div>
          <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight text-slate-900 mb-8 leading-tight">
            Accounting that <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-indigo-600">thinks for you</span>.
          </h1>
          <p class="mt-4 text-xl text-slate-600 mb-10 max-w-2xl mx-auto">
            Facturino combines powerful invoicing with advanced AI to automate your bookkeeping, track expenses, and give you real-time financial insights.
          </p>
          <div class="flex flex-col sm:flex-row justify-center gap-4">
            <router-link to="/login" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold rounded-xl text-white bg-primary-600 hover:bg-primary-700 transition-all shadow-lg hover:shadow-primary-500/30 transform hover:-translate-y-1">
              Start Free Trial
              <svg class="ml-2 -mr-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </router-link>
            <a href="#how-it-works" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold rounded-xl text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-all shadow-sm hover:shadow-md">
              See How It Works
            </a>
          </div>
        </div>
        
        <!-- Hero Image / Dashboard Preview -->
        <div class="mt-20 relative mx-auto max-w-5xl">
          <div class="rounded-2xl bg-slate-900/5 p-2 ring-1 ring-inset ring-slate-900/10 lg:-m-4 lg:rounded-3xl lg:p-4">
            <div class="rounded-xl bg-white shadow-2xl ring-1 ring-slate-900/10 overflow-hidden">
               <!-- Placeholder for Dashboard Image - In a real scenario, we'd use an actual screenshot -->
               <div class="aspect-[16/9] bg-slate-50 flex items-center justify-center relative overflow-hidden group">
                  <div class="absolute inset-0 bg-gradient-to-br from-slate-100 to-slate-50"></div>
                  <div class="relative z-10 text-center p-10">
                    <div class="w-full h-full flex flex-col items-center justify-center space-y-4 opacity-50">
                        <svg class="w-24 h-24 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="text-slate-400 font-medium">Dashboard Preview</span>
                    </div>
                  </div>
                  <!-- Floating Elements for "AI" feel -->
                  <div class="absolute top-10 right-10 bg-white p-4 rounded-lg shadow-xl border border-slate-100 animate-bounce-slow">
                      <div class="flex items-center space-x-3">
                          <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                          </div>
                          <div>
                              <p class="text-xs text-slate-500">Invoice #1024</p>
                              <p class="text-sm font-bold text-slate-800">Paid • $1,200.00</p>
                          </div>
                      </div>
                  </div>
                   <div class="absolute bottom-10 left-10 bg-white p-4 rounded-lg shadow-xl border border-slate-100 animate-bounce-slow delay-700">
                      <div class="flex items-center space-x-3">
                          <div class="h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                          </div>
                          <div>
                              <p class="text-xs text-slate-500">AI Insight</p>
                              <p class="text-sm font-bold text-slate-800">Expenses down 12%</p>
                          </div>
                      </div>
                  </div>
               </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Background decoration -->
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full z-0 pointer-events-none">
          <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-200/30 rounded-full blur-3xl mix-blend-multiply filter opacity-70 animate-blob"></div>
          <div class="absolute top-0 right-1/4 w-96 h-96 bg-indigo-200/30 rounded-full blur-3xl mix-blend-multiply filter opacity-70 animate-blob animation-delay-2000"></div>
          <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-pink-200/30 rounded-full blur-3xl mix-blend-multiply filter opacity-70 animate-blob animation-delay-4000"></div>
      </div>
    </section>

    <!-- Partners Section -->
    <section class="py-12 border-y border-slate-100 bg-slate-50/50">
      <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <h2 class="text-center text-lg font-semibold leading-8 text-gray-900">
          {{ $t('landing.partners.trusted_by') }}
        </h2>
        <div
          class="mx-auto mt-10 grid max-w-lg grid-cols-4 items-center gap-x-8 gap-y-10 sm:max-w-xl sm:grid-cols-6 sm:gap-x-10 lg:mx-0 lg:max-w-none lg:grid-cols-5"
        >    <!-- Placeholder Logos -->
            <div class="h-8 bg-slate-300 w-24 rounded"></div>
            <div class="h-8 bg-slate-300 w-24 rounded"></div>
            <div class="h-8 bg-slate-300 w-24 rounded"></div>
            <div class="h-8 bg-slate-300 w-24 rounded"></div>
            <div class="h-8 bg-slate-300 w-24 rounded"></div>
        </div>
      </div>
    </section>

    <!-- Benefits Section (Bento Grid) -->
    <section id="features" class="py-24 bg-white">
      <div class="container mx-auto px-4 md:px-6">
        <div class="text-center max-w-3xl mx-auto mb-16">
          <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Everything you need to run your business</h2>
          <p class="text-lg text-slate-600">Stop wrestling with spreadsheets. Facturino gives you a complete suite of tools to manage your finances effortlessly.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-2 lg:gap-x-8 lg:gap-y-16">
          <div class="relative pl-16">
            <dt class="text-base font-semibold leading-7 text-gray-900">
              <div
                class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600"
              >
                <BaseIcon name="SparklesIcon" class="h-6 w-6 text-white" />
              </div>
              {{ $t('landing.features.ai_insights.title') }}
            </dt>
            <dd class="mt-2 text-base leading-7 text-gray-600">
              {{ $t('landing.features.ai_insights.description') }}
            </dd>
          </div>
          <div class="relative pl-16">
            <dt class="text-base font-semibold leading-7 text-gray-900">
              <div
                class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600"
              >
                <BaseIcon name="DocumentTextIcon" class="h-6 w-6 text-white" />
              </div>
              {{ $t('landing.features.invoicing.title') }}
            </dt>
            <dd class="mt-2 text-base leading-7 text-gray-600">
              {{ $t('landing.features.invoicing.description') }}
            </dd>
          </div>
          <div class="relative pl-16">
            <dt class="text-base font-semibold leading-7 text-gray-900">
              <div
                class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600"
              >
                <BaseIcon name="CameraIcon" class="h-6 w-6 text-white" />
              </div>
              {{ $t('landing.features.expenses.title') }}
            </dt>
            <dd class="mt-2 text-base leading-7 text-gray-600">
              {{ $t('landing.features.expenses.description') }}
            </dd>
          </div>
          <div class="relative pl-16">
            <dt class="text-base font-semibold leading-7 text-gray-900">
              <div
                class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600"
              >
                <BaseIcon name="ChartBarIcon" class="h-6 w-6 text-white" />
              </div>
              {{ $t('landing.features.reporting.title') }}
            </dt>
            <dd class="mt-2 text-base leading-7 text-gray-600">
              {{ $t('landing.features.reporting.description') }}
            </dd>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-24 bg-slate-900 text-white">
      <div class="container mx-auto px-4 md:px-6">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-bold mb-4">Get started in minutes</h2>
          <p class="text-slate-400 text-lg">No credit card required. No complicated setup.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
           <!-- Connecting Line (Desktop) -->
           <div class="hidden md:block absolute top-12 left-1/6 right-1/6 h-0.5 bg-slate-700 z-0"></div>

           <!-- Step 1 -->
           <div class="relative z-10 text-center">
              <div class="w-24 h-24 mx-auto bg-slate-800 rounded-full flex items-center justify-center border-4 border-slate-900 mb-6 shadow-xl">
                 <span class="text-3xl font-bold text-primary-400">1</span>
              </div>
              <h3 class="text-xl font-bold mb-3">Create Account</h3>
              <p class="text-slate-400">Sign up for free and set up your company profile in less than 2 minutes.</p>
           </div>

           <!-- Step 2 -->
           <div class="relative z-10 text-center">
              <div class="w-24 h-24 mx-auto bg-slate-800 rounded-full flex items-center justify-center border-4 border-slate-900 mb-6 shadow-xl">
                 <span class="text-3xl font-bold text-primary-400">2</span>
              </div>
              <h3 class="text-xl font-bold mb-3">Connect Data</h3>
              <p class="text-slate-400">Import your clients and connect your bank account for automatic tracking.</p>
           </div>

           <!-- Step 3 -->
           <div class="relative z-10 text-center">
              <div class="w-24 h-24 mx-auto bg-slate-800 rounded-full flex items-center justify-center border-4 border-slate-900 mb-6 shadow-xl">
                 <span class="text-3xl font-bold text-primary-400">3</span>
              </div>
              <h3 class="text-xl font-bold mb-3">Get Paid</h3>
              <p class="text-slate-400">Send your first invoice and watch the payments roll in.</p>
           </div>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="py-24 bg-white">
      <div class="container mx-auto px-4 md:px-6">
        <h2 class="text-3xl md:text-4xl font-bold text-center text-slate-900 mb-16">Loved by businesses worldwide</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-slate-50 p-8 rounded-2xl border border-slate-100">
             <div class="flex text-yellow-400 mb-4">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" v-for="i in 5" :key="i"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
             </div>
             <p class="text-slate-700 mb-6">"Facturino changed how I handle my finances. The AI insights actually helped me cut costs by 15% in the first month."</p>
             <div class="flex items-center">
                <div class="w-10 h-10 bg-slate-200 rounded-full mr-3"></div>
                <div>
                   <p class="font-bold text-slate-900">Elena M.</p>
                   <p class="text-sm text-slate-500">Freelance Designer</p>
                </div>
             </div>
          </div>
          
           <div class="bg-slate-50 p-8 rounded-2xl border border-slate-100">
             <div class="flex text-yellow-400 mb-4">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" v-for="i in 5" :key="i"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
             </div>
             <p class="text-slate-700 mb-6">"The best invoicing software I've used. Simple, clean, and the recurring invoices save me hours every week."</p>
             <div class="flex items-center">
                <div class="w-10 h-10 bg-slate-200 rounded-full mr-3"></div>
                <div>
                   <p class="font-bold text-slate-900">Marko P.</p>
                   <p class="text-sm text-slate-500">Small Business Owner</p>
                </div>
             </div>
          </div>

           <div class="bg-slate-50 p-8 rounded-2xl border border-slate-100">
             <div class="flex text-yellow-400 mb-4">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" v-for="i in 5" :key="i"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
             </div>
             <p class="text-slate-700 mb-6">"Finally, an accounting tool that doesn't feel like it was built in the 90s. The UI is beautiful and intuitive."</p>
             <div class="flex items-center">
                <div class="w-10 h-10 bg-slate-200 rounded-full mr-3"></div>
                <div>
                   <p class="font-bold text-slate-900">Sarah J.</p>
                   <p class="text-sm text-slate-500">Marketing Consultant</p>
                </div>
             </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-24 bg-slate-50">
       <div class="container mx-auto px-4 md:px-6 max-w-4xl">
          <h2 class="text-3xl md:text-4xl font-bold text-center text-slate-900 mb-12">Frequently Asked Questions</h2>
          
          <div class="space-y-4">
             <div v-for="(faq, index) in faqs" :key="index" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <button @click="faq.open = !faq.open" class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none">
                   <span class="font-semibold text-slate-900">{{ faq.question }}</span>
                   <svg class="w-5 h-5 text-slate-500 transform transition-transform" :class="{'rotate-180': faq.open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                   </svg>
                </button>
                <div v-show="faq.open" class="px-6 pb-4 text-slate-600">
                   {{ faq.answer }}
                </div>
             </div>
          </div>
       </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 bg-slate-900 relative overflow-hidden">
       <div class="absolute inset-0 overflow-hidden">
          <div class="absolute -top-1/2 -right-1/2 w-full h-full bg-primary-900/20 rounded-full blur-3xl"></div>
          <div class="absolute -bottom-1/2 -left-1/2 w-full h-full bg-indigo-900/20 rounded-full blur-3xl"></div>
       </div>
       
       <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
          <h2
            class="text-3xl font-bold tracking-tight text-white sm:text-4xl"
          >
            {{ $t('landing.cta_footer.title') }}<br />{{ $t('landing.cta_footer.subtitle') }}
          </h2>
          <div class="mt-10 flex items-center justify-center gap-x-6">
            <router-link
              to="/login"
              class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-primary-600 shadow-sm hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
            >
              {{ $t('landing.cta_footer.button') }}
            </router-link>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const faqs = ref([
   {
      question: 'Is Facturino free to use?',
      answer: 'We offer a generous free plan for small businesses with basic needs. For more advanced features like AI insights and unlimited invoices, we have affordable premium plans.',
      open: false
   },
   {
      question: 'Can I cancel my subscription anytime?',
      answer: 'Yes, absolutely. There are no long-term contracts or cancellation fees. You can upgrade, downgrade, or cancel your plan at any time.',
      open: false
   },
   {
      question: 'Is my data secure?',
      answer: 'Security is our top priority. We use bank-level encryption to protect your data and perform regular backups to ensure your information is always safe.',
      open: false
   },
   {
      question: 'Do you support multiple currencies?',
      answer: 'Yes, our Business and Max plans support multi-currency invoicing and expense tracking with automatic exchange rate updates.',
      open: false
   }
])
</script>

<style scoped>
.animate-bounce-slow {
  animation: bounce 3s infinite;
}
.animate-blob {
  animation: blob 7s infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}
.animation-delay-4000 {
  animation-delay: 4s;
}

@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
</style>
