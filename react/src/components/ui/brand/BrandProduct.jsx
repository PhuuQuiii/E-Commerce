import React, { useEffect, useState } from 'react';
import { productWithBrand } from '../../../services/productService';
import { Link, useParams } from "react-router-dom";
import { Typography } from "@material-tailwind/react";
import { Button, IconButton } from "@material-tailwind/react";
import { ArrowRightIcon, ArrowLeftIcon } from "@heroicons/react/24/outline";
import Cart from "../../../assets/icon/add-to-cart.svg";
import Star0 from "../../../assets/icon/star-svgrepo-com.svg";
import Star1 from "../../../assets/icon/star-outline.svg";

export default function BrandProduct() {

    const { brandId } = useParams();

    const [data, setData] = useState([]);
    const [dataFull, setDataFull] = useState([]);
    const [page, setPage] = useState(1);
    const [active, setActive] = useState(1);
    const [visiblePages, setVisiblePages] = useState([]);

    useEffect(() => {

        const fetchData = async () => {
            try {
                let res = await productWithBrand(brandId, page);
                if (res && res.data && res.data.data) {
                    setData(res.data.data);
                    setDataFull(res.data);
                }
            } catch (error) {
                console.error("Error: ", error);
            }
        };
        fetchData();
    }, [brandId, page]);

    const getItemProps = (index) => ({
        variant: active === index ? 'filled' : 'text',
        color: 'gray',
        onClick: () => {
            setPage(index);
            setActive(index);
        },
    });

    const next = () => {
        if (active === dataFull.last_page) return;

        setActive(active + 1);
        setPage(active + 1);
    };

    const prev = () => {
        if (active === dataFull.from) return;

        setActive(active - 1);
        setPage(active - 1);
    };

    useEffect(() => {
        const calculateVisiblePages = async () => {
            const totalVisiblePages = 3;
            const totalPageCount = dataFull.last_page;

            let startPage, endPage;
            if (totalPageCount <= totalVisiblePages) {
                startPage = 1;
                endPage = totalPageCount;
            } else {
                const middlePage = Math.floor(totalVisiblePages / 2);
                if (active <= middlePage + 1) {
                    startPage = 1;
                    endPage = totalVisiblePages;
                } else if (active >= totalPageCount - middlePage) {
                    startPage = totalPageCount - totalVisiblePages + 1;
                    endPage = totalPageCount;
                } else {
                    startPage = active - middlePage;
                    endPage = active + middlePage;
                }
            }

            const visiblePagesArray = Array.from({ length: endPage - startPage + 1 }, (_, index) => startPage + index);
            setVisiblePages(visiblePagesArray);
        };

        calculateVisiblePages();
    }, [active, dataFull.last_page]);

    console.log(data)
    // console.log(dataFull)

    return (
        <div className=' flex justify-center items-center'>
            <div className=' w-[95%] md:w-[90%] lg:w-[80%]'>
                <div className=" w-full">
                    <section
                        id="Projects"
                        className=" mx-auto grid grid-cols-2 lg:grid-cols-4  md:grid-cols-3 2xl:grid-cols-5 justify-items-center justify-center gap-y-8 gap-x-6 mt-6 mb-5"
                    >
                        {data &&
                            data.length > 0 ?
                            (data.map((product, index) => (
                                <Link key={index} to={`/product/${product.product_id}`}>
                                    <div className="w-full h-[350px] md:h-[380px] lg:h-[450px] xl:h-[510px]  bg-white shadow-md shadow-gray-300 rounded-xl duration-500 hover:scale-105 hover:shadow-2xl">
                                        {product.images?.[0]?.image_url ? (
                                            <img
                                                className=" h-[200px] w-[200px] md:h-[220px] md:w-[220px] lg:h-[280px] lg:w-[280px] xl:h-[320px] xl:w-[320px] object-cover rounded-t-xl"
                                                src={`../../../src/assets/image/${product.images?.[0]?.image_url}`}
                                                alt="Product"
                                            />
                                        ) : (
                                            <img
                                                className=" h-[200px] w-[200px] md:h-[220px] md:w-[220px] lg:h-[280px] lg:w-[280px] xl:h-[320px] xl:w-[320px] object-cover rounded-t-xl"
                                                src={`https://upload.wikimedia.org/wikipedia/commons/thumb/6/65/No-Image-Placeholder.svg/1665px-No-Image-Placeholder.svg.png`}
                                                alt="Product"
                                            />
                                        )}
                                        <div className="px-4 py-3 h-[20%] w-full">
                                            <p className="text-md lg:text-lg xl:text-xl font-bold text-black truncate block capitalize">{product.name}</p>
                                            <div className="space-x-1 flex justify-center mt-2 md:mt-4 lg:mt-6 xl:mt-8">
                                                {Array.from({ length: Math.round(product.average_rating) }, (_, index) => (
                                                    <img className="w-5" key={index} src={Star0} alt="" />
                                                ))}
                                                {Array.from({ length: 5 - Math.round(product.average_rating) }, (_, index) => (
                                                    <img className="w-5" key={index} src={Star1} alt="" />
                                                ))}
                                            </div>
                                            <div className="flex items-center">
                                                <p className="text-md lg:text-lg xl:text-xl font-semibold text-black cursor-auto my-1">${(product.price - (product.price) * 0.3).toFixed(2)}</p>
                                                <del>
                                                    <p className="text-sm text-gray-600 cursor-auto ml-2">${product.price}</p>
                                                </del>
                                                <div className="ml-auto">
                                                    <img src={Cart} alt="" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </Link>

                            ))) : (
                                <Typography color="gray">
                                    Sorry, We don't have what are you looking for!
                                </Typography>
                            )}
                    </section>
                    <div className=" flex justify-center">
                        <div className="flex items-center my-6 mt-12">
                            <Button
                                variant="text"
                                className="flex items-center gap-2"
                                onClick={prev}
                                disabled={active === dataFull.from}
                            >
                                <ArrowLeftIcon strokeWidth={2} className="h-4 w-4" /> Previous
                            </Button>

                            <div className="flex items-center gap-2">
                                {visiblePages.map((pageNumber) => (
                                    <IconButton
                                        key={pageNumber}
                                        {...getItemProps(pageNumber)}
                                    >
                                        {pageNumber}
                                    </IconButton>
                                ))}
                            </div>


                            <Button
                                variant="text"
                                className="flex items-center gap-2"
                                onClick={next}
                                disabled={active === dataFull.last_page}
                            >
                                Next
                                <ArrowRightIcon strokeWidth={2} className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>)
}
