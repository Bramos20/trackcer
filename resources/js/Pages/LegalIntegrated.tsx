import React, { useState } from "react"
import { Head } from "@inertiajs/react"
import AppLayout from "@/Layouts/AppLayout"
import { Shield, FileText, Eye, Lock, Users, AlertCircle, CheckCircle } from "lucide-react"

export default function LegalIntegrated({ auth }) {
  const [activeSection, setActiveSection] = useState('privacy')
  
  const today = new Date().toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  })

  const sections = [
    { id: 'privacy', label: 'Privacy Policy', icon: Shield },
    { id: 'terms', label: 'Terms of Use', icon: FileText }
  ]

  return (
    <AppLayout user={auth?.user}>
      <Head title="Legal" />
      
      <div className="w-full">
        <div className="space-y-6">
          <h1 className="text-3xl font-normal">Legal Notice</h1>
          
          {/* Navigation Tabs */}
          <div className="flex justify-start mb-8">
            <div className="inline-flex bg-white/[0.51] dark:bg-[#191919]/[0.58] backdrop-blur-md rounded-full p-0.5">
              {sections.map((section) => {
                const Icon = section.icon
                return (
                  <button
                    key={section.id}
                    onClick={() => setActiveSection(section.id)}
                    className={`flex items-center gap-3 px-6 py-3 rounded-full font-medium transition-all duration-300 ${
                      activeSection === section.id
                        ? 'bg-[#6A4BFB] dark:bg-white text-white dark:text-black'
                        : 'text-gray-600 dark:text-gray-400'
                    }`}
                  >
                    <Icon size={20} />
                    {section.label}
                  </button>
                )
              })}
            </div>
          </div>

          {/* Content Cards */}
          <div className="grid lg:grid-cols-3 gap-8">
            {/* Quick Info Sidebar */}
            <div className="lg:col-span-1 space-y-6">
              <div className="bg-white/[0.58] dark:bg-[#191919]/[0.58] backdrop-blur-md rounded-2xl p-6">
                <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                  <Eye size={20} className="text-blue-400" />
                  At a Glance
                </h3>
                <div className="space-y-4">
                  <div className="flex items-start gap-3">
                    <CheckCircle size={16} className="text-green-400 mt-1 flex-shrink-0" />
                    <div>
                      <div className="text-gray-900 dark:text-white font-medium">No Data Sales</div>
                      <div className="text-sm text-gray-600 dark:text-gray-300">We never sell your personal information</div>
                    </div>
                  </div>
                  <div className="flex items-start gap-3">
                    <CheckCircle size={16} className="text-green-400 mt-1 flex-shrink-0" />
                    <div>
                      <div className="text-gray-900 dark:text-white font-medium">Full Control</div>
                      <div className="text-sm text-gray-600 dark:text-gray-300">Revoke access anytime</div>
                    </div>
                  </div>
                  <div className="flex items-start gap-3">
                    <CheckCircle size={16} className="text-green-400 mt-1 flex-shrink-0" />
                    <div>
                      <div className="text-gray-900 dark:text-white font-medium">Secure Storage</div>
                      <div className="text-sm text-gray-600 dark:text-gray-300">Industry-standard encryption</div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="bg-gradient-to-br from-blue-600/20 to-slate-600/20 backdrop-blur-md border border-blue-500/30 rounded-2xl p-6">
                <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                  <Users size={20} className="text-blue-400" />
                  Need Help?
                </h3>
                <p className="text-gray-700 dark:text-gray-300 mb-4">
                  Have questions about your privacy or our terms? We're here to help.
                </p>
                <a 
                  href="mailto:support@trackcer.com"
                  className="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 border border-white/30 rounded-lg text-gray-900 dark:text-white font-medium transition-all duration-300 hover:scale-105"
                >
                  Contact Support
                </a>
              </div>
            </div>

            {/* Main Content */}
            <div className="lg:col-span-2">
              <div className="bg-white/[0.58] dark:bg-[#191919]/[0.58] backdrop-blur-md rounded-2xl overflow-hidden">
                {activeSection === 'privacy' && (
                  <div className="p-8 space-y-8">
                    <div className="border-b border-gray-200 dark:border-gray-700 pb-6">
                      <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                        <Shield className="text-blue-400" size={32} />
                        Privacy Policy
                      </h2>
                      <p className="text-gray-700 dark:text-gray-300 text-lg leading-relaxed">
                        TrackCer ("we", "our", or "us") values your privacy. This Privacy Policy outlines how we collect, use, and protect your data when using our platform, including integrations with services like Spotify and Apple Music.
                      </p>
                    </div>

                    <div className="space-y-8">
                      <section>
                        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                          <div className="w-8 h-8 bg-blue-600/20 rounded-lg flex items-center justify-center">
                            <span className="text-blue-400 font-bold">1.1</span>
                          </div>
                          Information We Collect
                        </h3>
                        <div className="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 space-y-4">
                          <div className="flex items-start gap-3">
                            <div className="w-2 h-2 bg-blue-400 rounded-full mt-3"></div>
                            <div>
                              <div className="text-gray-900 dark:text-white font-medium">Music Service Data</div>
                              <div className="text-gray-600 dark:text-gray-400">Spotify & Apple Music: name, email, listening history, playlists, followed artists</div>
                            </div>
                          </div>
                          <div className="flex items-start gap-3">
                            <div className="w-2 h-2 bg-blue-400 rounded-full mt-3"></div>
                            <div>
                              <div className="text-gray-900 dark:text-white font-medium">Technical Information</div>
                              <div className="text-gray-600 dark:text-gray-400">IP address, browser type, device information</div>
                            </div>
                          </div>
                          <div className="flex items-start gap-3">
                            <div className="w-2 h-2 bg-blue-400 rounded-full mt-3"></div>
                            <div>
                              <div className="text-gray-900 dark:text-white font-medium">Usage Analytics</div>
                              <div className="text-gray-600 dark:text-gray-400">How you interact with TrackCer features</div>
                            </div>
                          </div>
                        </div>
                      </section>

                      <section>
                        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                          <div className="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center">
                            <span className="text-blue-400 font-bold">1.2</span>
                          </div>
                          How We Use Your Information
                        </h3>
                        <div className="grid gap-4">
                          <div className="bg-gradient-to-r from-blue-600/10 to-slate-600/10 border border-blue-500/20 rounded-xl p-4">
                            <div className="text-gray-900 dark:text-white font-medium mb-2">Personalized Experience</div>
                            <div className="text-gray-600 dark:text-gray-400">Deliver customized insights and music recommendations</div>
                          </div>
                          <div className="bg-gradient-to-r from-slate-600/10 to-gray-600/10 border border-slate-500/20 rounded-xl p-4">
                            <div className="text-gray-900 dark:text-white font-medium mb-2">Service Improvement</div>
                            <div className="text-gray-600 dark:text-gray-400">Enhance features and user experience</div>
                          </div>
                          <div className="bg-gradient-to-r from-gray-600/10 to-slate-700/10 border border-gray-500/20 rounded-xl p-4">
                            <div className="text-gray-900 dark:text-white font-medium mb-2">Compliance</div>
                            <div className="text-gray-600 dark:text-gray-400">Meet third-party API requirements (e.g., Spotify Terms)</div>
                          </div>
                        </div>
                      </section>

                      <section>
                        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                          <div className="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center">
                            <span className="text-green-400 font-bold">1.3</span>
                          </div>
                          Information Sharing
                        </h3>
                        <div className="bg-red-500/10 border border-red-400/20 rounded-xl p-6 mb-4">
                          <div className="flex items-center gap-3 mb-3">
                            <AlertCircle className="text-red-400" size={20} />
                            <div className="text-red-400 font-semibold">Important Promise</div>
                          </div>
                          <div className="text-gray-900 dark:text-white font-medium">We do not sell or rent your personal data. Ever.</div>
                        </div>
                        <div className="text-gray-700 dark:text-gray-300 mb-4">Data is shared only with:</div>
                        <div className="space-y-3">
                          <div className="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                            <CheckCircle size={16} className="text-green-400" />
                            Spotify (as authorized by you)
                          </div>
                          <div className="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                            <CheckCircle size={16} className="text-green-400" />
                            Essential service providers (hosting, analytics)
                          </div>
                        </div>
                      </section>

                      <section>
                        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                          <div className="w-8 h-8 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                            <span className="text-yellow-400 font-bold">1.4</span>
                          </div>
                          Your Rights & Control
                        </h3>
                        <div className="grid gap-4">
                          <div className="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-300">
                            <div className="text-gray-900 dark:text-white font-medium">✋ Revoke Access</div>
                            <div className="text-gray-600 dark:text-gray-400">Disconnect Spotify integration anytime</div>
                          </div>
                          <div className="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-300">
                            <div className="text-gray-900 dark:text-white font-medium">🗑️ Delete Data</div>
                            <div className="text-gray-600 dark:text-gray-400">Request complete account and data deletion</div>
                          </div>
                          <div className="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-300">
                            <div className="text-gray-900 dark:text-white font-medium">📧 Get Support</div>
                            <div className="text-gray-600 dark:text-gray-400">Contact us with any privacy concerns</div>
                          </div>
                        </div>
                      </section>

                      <section>
                        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                          <div className="w-8 h-8 bg-red-500/20 rounded-lg flex items-center justify-center">
                            <span className="text-red-400 font-bold">1.5</span>
                          </div>
                          Data Security
                        </h3>
                        <div className="bg-gradient-to-r from-slate-600/10 to-blue-600/10 border border-slate-500/20 rounded-xl p-6">
                          <div className="text-gray-900 dark:text-white mb-3">We employ reasonable security measures to protect your information, including:</div>
                          <div className="grid grid-cols-2 gap-3 text-sm">
                            <div className="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                              <Lock size={14} className="text-blue-400" />
                              Encryption at rest
                            </div>
                            <div className="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                              <Shield size={14} className="text-blue-400" />
                              Secure transmission
                            </div>
                          </div>
                          <div className="text-gray-600 dark:text-gray-400 text-sm mt-3">However, no system is completely secure. We continuously work to improve our protections.</div>
                        </div>
                      </section>
                    </div>
                  </div>
                )}

                {activeSection === 'terms' && (
                  <div className="p-8 space-y-8">
                    <div className="border-b border-gray-200 dark:border-gray-700 pb-6">
                      <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                        <FileText className="text-blue-400" size={32} />
                        Terms of Use
                      </h2>
                      <p className="text-gray-700 dark:text-gray-300 text-lg leading-relaxed">
                        These Terms govern your use of TrackCer and its integrations. By using our platform, you agree to these Terms. If you disagree, please discontinue use.
                      </p>
                    </div>

                    <div className="space-y-8">
                      <section>
                        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                          <div className="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center">
                            <span className="text-blue-400 font-bold">2.1</span>
                          </div>
                          Acceptance of Terms
                        </h3>
                        <div className="bg-blue-500/10 border border-blue-400/20 rounded-xl p-6">
                          <div className="text-gray-900 dark:text-white">By accessing or using TrackCer, you agree to be bound by these Terms and our Privacy Policy.</div>
                        </div>
                      </section>

                      <section>
                        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                          <div className="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center">
                            <span className="text-purple-400 font-bold">2.2</span>
                          </div>
                          Eligibility & Conduct
                        </h3>
                        <div className="space-y-4">
                          <div className="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                            <div className="text-gray-900 dark:text-white font-medium mb-2">Age Requirement</div>
                            <div className="text-gray-600 dark:text-gray-400">You must be 13 years or older to use TrackCer</div>
                          </div>
                          <div className="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                            <div className="text-gray-900 dark:text-white font-medium mb-2">Legal Use Only</div>
                            <div className="text-gray-600 dark:text-gray-400">You agree not to use TrackCer for any illegal purposes</div>
                          </div>
                          <div className="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                            <div className="text-gray-900 dark:text-white font-medium mb-2">API Compliance</div>
                            <div className="text-gray-600 dark:text-gray-400">You must comply with Spotify's and Apple Music's API Terms</div>
                          </div>
                        </div>
                      </section>

                      <section>
                        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                          <div className="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center">
                            <span className="text-green-400 font-bold">2.3</span>
                          </div>
                          Account Security
                        </h3>
                        <div className="bg-gradient-to-r from-green-500/10 to-blue-500/10 border border-green-400/20 rounded-xl p-6">
                          <div className="space-y-4">
                            <div className="flex items-start gap-3">
                              <Lock size={20} className="text-green-400 mt-1" />
                              <div>
                                <div className="text-gray-900 dark:text-white font-medium">Credential Protection</div>
                                <div className="text-gray-600 dark:text-gray-400">You are responsible for safeguarding your login credentials</div>
                              </div>
                            </div>
                            <div className="flex items-start gap-3">
                              <AlertCircle size={20} className="text-yellow-400 mt-1" />
                              <div>
                                <div className="text-gray-900 dark:text-white font-medium">Report Issues</div>
                                <div className="text-gray-600 dark:text-gray-400">Inform us immediately of any unauthorized account activity</div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </section>

                      <section>
                        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                          <div className="w-8 h-8 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                            <span className="text-yellow-400 font-bold">2.4</span>
                          </div>
                          Third-Party Integration
                        </h3>
                        <div className="bg-yellow-500/10 border border-yellow-400/20 rounded-xl p-6">
                          <div className="text-gray-900 dark:text-white mb-3">Important Notice:</div>
                          <div className="text-gray-700 dark:text-gray-300">
                            TrackCer connects with Spotify and Apple Music APIs. Your use of our platform also binds you to their respective terms of service and licensing agreements.
                          </div>
                        </div>
                      </section>

                      <section>
                        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                          <div className="w-8 h-8 bg-red-500/20 rounded-lg flex items-center justify-center">
                            <span className="text-red-400 font-bold">2.5</span>
                          </div>
                          Service Disclaimer
                        </h3>
                        <div className="bg-red-500/10 border border-red-400/20 rounded-xl p-6">
                          <div className="text-red-400 font-semibold mb-3">As-Is Service</div>
                          <div className="text-gray-700 dark:text-gray-300 mb-4">
                            TrackCer is provided "as-is." We make no guarantees about service availability, accuracy, or fitness for any particular purpose.
                          </div>
                          <div className="text-gray-600 dark:text-gray-400 text-sm">
                            We are not responsible for any damages resulting from your use of the service, including but not limited to data loss, service interruptions, or integration failures.
                          </div>
                        </div>
                      </section>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Footer Contact */}
          <footer className="mt-16">
            <div className="bg-white/[0.58] dark:bg-[#191919]/[0.58] backdrop-blur-md rounded-2xl p-8">
              <div className="flex items-center justify-center gap-3 mb-4">
                <div className="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                  <FileText className="text-white" size={24} />
                </div>
                <div>
                  <div className="text-xl font-semibold text-gray-900 dark:text-white">Questions or Concerns?</div>
                  <div className="text-gray-600 dark:text-gray-400">We're here to help with any legal questions</div>
                </div>
              </div>
              <div className="text-center">
                <a 
                  href="mailto:support@trackcer.com"
                  className="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold rounded-xl transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-purple-500/25"
                >
                  Contact Support Team
                </a>
              </div>
            </div>
          </footer>
        </div>
      </div>
    </AppLayout>
  )
}