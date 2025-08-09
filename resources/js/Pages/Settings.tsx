import React, { useState, useEffect } from "react";
import { useForm, usePage } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";
import { User, Upload, Save, Mail, Camera, Check } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import profile from "@/assets/Profile.png";
import topRightLg from "@/assets/top-right-lg.png";
import { cn } from "@/lib/utils";

export default function Settings({ auth }) {
    const { props } = usePage();
    const { user } = props;

    const [preview, setPreview] = useState(user.profile_image);
    const [uploading, setUploading] = useState(false);
    const [uploadSuccess, setUploadSuccess] = useState(false);
    const [isDark, setIsDark] = useState(false);
    const [windowWidth, setWindowWidth] = useState(
        typeof window !== "undefined" ? window.innerWidth : 1024
    );

    useEffect(() => {
        // Check initial theme
        setIsDark(document.documentElement.classList.contains("dark"));

        // Watch for theme changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === "class") {
                    setIsDark(
                        document.documentElement.classList.contains("dark")
                    );
                }
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ["class"],
        });

        // Handle window resize
        const handleResize = () => setWindowWidth(window.innerWidth);
        window.addEventListener("resize", handleResize);

        return () => {
            observer.disconnect();
            window.removeEventListener("resize", handleResize);
        };
    }, []);

    const { data, setData, post, processing, errors } = useForm({
        name: user.name,
        profile_image: null,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("settings.updateProfile"));
    };

    const handleImageUpload = async (e) => {
        const file = e.target.files[0];
        if (file) {
            setUploading(true);
            setData("profile_image", file);
            setPreview(URL.createObjectURL(file));

            const formData = new FormData();
            formData.append("profile_image", file);

            try {
                await fetch(route("settings.updateProfileImage"), {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });
                setUploadSuccess(true);
                setTimeout(() => setUploadSuccess(false), 3000);
            } catch (error) {
                console.error("Upload failed:", error);
            } finally {
                setUploading(false);
            }
        }
    };

    return (
        <AppLayout user={auth.user}>
            <div className="relative">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl lg:text-5xl lg:mb-3 font-normal">
                        Account Settings
                    </h1>
                    <p className="lg:text-lg text-muted-foreground font-montserrat">
                        Manage your profile and account preferences
                    </p>
                </div>
                {/* save change button curve  */}
                <Button
                    variant="default"
                    size="sm"
                    className="bg-primary ml-auto text-xs hover:bg-primary/90 text-primary-foreground py-5.5 px-12 rounded-2xl hidden lg:inline-flex absolute right-0 top-[7.6rem] z-10"
                    disabled={processing}
                    onClick={handleSubmit}
                >
                    {/* {processing ? "SAVING..." : "SAVE CHANGES"} */}
                    SAVE CHANGES
                </Button>
                <Card
                    className="settingCurve w-full dark:bg-[#19191929]"
                    style={{
                        borderTopRightRadius: "0rem",
                        backdropFilter: "blur(40px)",
                        WebkitMask: `url(${topRightLg}) center / contain no-repeat, linear-gradient(#000000 0 0)`,
                        maskSize: "14rem 5rem",
                        maskPosition: "top right",
                        maskComposite: "exclude",
                    }}
                >
                    <CardHeader>
                        <CardTitle>
                            <div className="flex items-center gap-2 font-normal font-montserrat whitespace-nowrap">
                                <User size={20} />
                                <span>Account Information</span>
                            </div>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col lg:flex-row items-center gap-5">
                            {/* profile image  */}
                            <div className="group relative min-w-[245px] max-w-[245px] flex justify-center xl:items-start">
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={handleImageUpload}
                                    hidden
                                    id="profile_upload"
                                    disabled={uploading}
                                />
                                <label htmlFor="profile_upload">
                                    <img
                                        src={
                                            preview ||
                                            "https://fls-9f190778-62c1-4c3c-bf29-79adcf96285e.laravel.cloud/profile_images/12/GwASqNfhCKIqH4FAUp5VTew6WzcRtIRyE2NNU3Kn.png"
                                        }
                                        alt="profile"
                                        className="w-full rounded-3xl"
                                    />

                                    <div
                                        className={cn(
                                            "absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-16 h-16 rounded-full bg-[#00000040] flex items-center justify-center cursor-pointer backdrop-blur-3xl opacity-0 invisible transition-all duration-300 group-hover:opacity-100 group-hover:visible",
                                            (uploadSuccess || uploading) &&
                                                "opacity-100 visible"
                                        )}
                                    >
                                        {uploading ? (
                                            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
                                        ) : uploadSuccess ? (
                                            <Check className="w-8 h-8 md:w-10 md:h-10 text-green-400" />
                                        ) : (
                                            <Camera className="w-8 h-8 md:w-10 md:h-10 text-white" />
                                        )}
                                    </div>
                                </label>
                            </div>
                            {/* profile input  */}
                            <form className="w-full xl:w-[70rem]  flex flex-col gap-6 bg-[#FFFFFF29] p-7 rounded-3xl dark:bg-[#30303029]">
                                <div className="flex flex-col gap-2">
                                    <label
                                        htmlFor="name"
                                        className="font-medium font-montserrat"
                                    >
                                        Display Name
                                    </label>
                                    <div className="relative">
                                        <input
                                            id="name"
                                            type="text"
                                            value={data.name}
                                            onChange={(e) =>
                                                setData("name", e.target.value)
                                            }
                                            placeholder="Enter your display name"
                                            className="bg-white/[0.58] dark:bg-[#44444429] rounded-full pl-16 pr-32 py-3.5 h-[52px] w-full focus:outline-none placeholder:text-gray-500 dark:placeholder:text-gray-400 text-gray-900 dark:text-white"
                                        />
                                        <div className="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#E4E4E4] dark:bg-[#D9D9D91C] flex items-center justify-center">
                                            <User className="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                        </div>
                                        {errors.name && (
                                            <p className="text-red-500 text-sm mt-2">
                                                {errors.name}
                                            </p>
                                        )}
                                    </div>
                                </div>
                                <div className="flex flex-col gap-2">
                                    <label
                                        htmlFor="email_address"
                                        className="font-medium font-montserrat"
                                    >
                                        Email Address
                                    </label>
                                    <div className="relative">
                                        <input
                                            id="email_address"
                                            type="text"
                                            value={user.email}
                                            readOnly
                                            placeholder="tukijoshua1@gmail.com"
                                            className="bg-white/[0.58] dark:bg-[#44444429] rounded-full pl-16 pr-32 py-3.5 h-[52px] w-full focus:outline-none placeholder:text-gray-500 dark:placeholder:text-gray-400 text-gray-900 dark:text-white"
                                        />
                                        {/* verified  */}
                                        <div className="absolute right-2 top-1/2 -translate-y-1/2 px-4 h-10 rounded-full bg-[#00A63E20] text-[#00A63E] flex items-center justify-center">
                                            Verified
                                        </div>
                                        <div className="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#E4E4E4] dark:bg-[#D9D9D91C] flex items-center justify-center">
                                            <Mail className="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                        </div>
                                    </div>
                                </div>
                                <Button
                                    variant="default"
                                    size="sm"
                                    className="bg-primary w-fit ml-auto text-xs hover:bg-primary/90 text-primary-foreground py-5 !px-4 rounded-2xl lg:hidden"
                                    disabled={processing}
                                    onClick={handleSubmit}
                                >
                                    {/* {processing ? "SAVING..." : "SAVE CHANGES"} */}
                                    SAVE CHANGES
                                </Button>
                            </form>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
