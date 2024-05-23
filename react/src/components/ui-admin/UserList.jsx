import React from "react";

import {
    ChevronUpDownIcon,
    ArrowRightIcon,
    ArrowLeftIcon,
    Square3Stack3DIcon,
    UserCircleIcon,
    Cog6ToothIcon,
    MagnifyingGlassIcon
} from "@heroicons/react/24/outline";
import {
    EyeIcon,
    PencilIcon,
    TrashIcon
} from "@heroicons/react/24/solid";
import {
    Card,
    Typography,
    CardBody,
    CardFooter,
    IconButton,
    Tooltip,
    Tabs,
    TabsHeader,
    TabsBody,
    Tab,
    TabPanel,
    Button,
    Input,
    Popover,
    PopoverHandler,
    PopoverContent,
} from "@material-tailwind/react";

import { useEffect, useState } from "react";
import { fetchAllUser, userDelete, userAddress, searchUser } from "../../services/authService";

const TABLE_HEAD = [
    "Avt",
    "ID",
    "Username",
    "Email",
    "Warehouse",
    "Revenue",
    "Detail",
    "Edit",
    "Delete"
];

const FetchAllUser = ({ type_id }) => {

    const [data, setData] = useState([]);
    const [dataFull, setDataFull] = useState([]);
    const [page, setPage] = useState(1);

    useEffect(() => {
        const fetchData = async () => {
            try {
                let res = await fetchAllUser(type_id, page);
                setData(res.data.data);
                setDataFull(res.data);
            } catch (error) {
                console.error(error);
            }
        }
        fetchData();
    }, [page]);

    const [active, setActive] = useState(1);
    const [visiblePages, setVisiblePages] = useState([]);

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

    const EditUser = () => {


        return (
            <div className="cursor-pointer flex justify-center hover:bg-blue-gray-50 py-2 rounded-lg">
                <Popover placement="left">
                    <PopoverHandler>
                        <div className=" w-full flex justify-center">
                            <PencilIcon className=" w-4 h-4" />
                        </div>
                    </PopoverHandler>
                    <PopoverContent className="flex flex-col z-10 p-8 gap-4">

                        <div className=" flex flex-col gap-2">
                            <div>
                                Change Email
                            </div>
                            <div>
                                <Input
                                    label="Input"
                                />
                            </div>
                        </div>

                        <div className=" flex flex-col gap-2">
                            <div>
                                Change Password
                            </div>
                            <div>
                                <Input
                                    label="Input"
                                />
                            </div>
                        </div>

                        <div className=" flex my-8">
                            <div className=" w-[85%]">
                                <Button>Update</Button>
                            </div>
                        </div>
                    </PopoverContent>
                </Popover>
            </div>
        )
    }

    const DeleteUser = ({ user_id }) => {
        const handleDelete = async () => {
            try {
                let res = await userDelete(user_id);
                console.log(res);
            } catch (error) {
                console.error(error);
            }
        }

        return (
            <div onClick={handleDelete} className="cursor-pointer flex justify-center hover:bg-blue-gray-50 py-2 rounded-lg " >
                <TrashIcon className="w-4 h-4 " />
            </div>
        )
    }


    const UserAddress = ({ user_id }) => {
        const [data, setData] = useState([]);

        useEffect(() => {
            const fetchData = async () => {
                try {
                    let res = await userAddress(user_id);
                    setData(res.data);
                } catch (error) {
                    console.error(error);
                }
            }
            fetchData();
        }, []);

        return (
            <div className="w-80 font-normal text-base leading-6 text-gray-800">
                {
                    data && data.length > 0 && data.map((item, index) => (
                        <div key={index}>
                            <Typography className=" font-semibold">
                                Address {index + 1}
                            </Typography>
                            <Typography
                                variant="small"
                                className="font-normal opacity-80"
                            >
                                {item.country}, {item.province}, {item.district}, {item.commune}, {item.street}, {item.number}
                            </Typography>
                        </div>
                    ))
                }
            </div>
        );
    };

    return (
        <div>
            <CardBody className="px-4">
                <table className=" w-full min-w-max table-auto text-left">
                    <thead>
                        <tr>
                            {TABLE_HEAD.map((head, index) => (
                                <th
                                    key={head}
                                    className="cursor-pointer w-fit border-y border-blue-gray-100 bg-blue-gray-50/50 p-4 transition-colors hover:bg-blue-gray-50"
                                >
                                    <Typography
                                        variant="small"
                                        color="blue-gray"
                                        className="flex items-center justify-between font-normal leading-none opacity-70"
                                    >
                                        {head}{" "}
                                        {index !== TABLE_HEAD.length - 1 && (
                                            <ChevronUpDownIcon
                                                strokeWidth={2}
                                                className="h-4 w-4"
                                            />
                                        )}
                                    </Typography>
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {data.map(
                            (users, index) => {
                                const isLast = index === data.length - 1;
                                const classes = isLast
                                    ? "p-4"
                                    : "p-4 border-b border-blue-gray-50";

                                return (
                                    <tr key={index}>
                                        <td className={classes}>
                                            <div className="flex items-center gap-3">
                                                <div className="flex flex-col">
                                                    <img className=" w-10 h-10 rounded-full object-cover" src={`../../../src/assets/shop/${users.avt_image}`} alt="" />
                                                </div>
                                            </div>
                                        </td>
                                        <td className={classes}>
                                            <div className="flex flex-col">
                                                <Typography
                                                    variant="small"
                                                    color="blue-gray"
                                                    className="font-normal"
                                                >
                                                    {users.user_id || ''}
                                                </Typography>
                                            </div>
                                        </td>
                                        <td className={classes}>
                                            <div className="w-max">
                                                <Typography
                                                    variant="small"
                                                    color="blue-gray"
                                                    className="font-normal"
                                                >
                                                    {users.username || ''}
                                                </Typography>
                                            </div>
                                        </td>
                                        <td className={classes}>
                                            <Typography
                                                variant="small"
                                                color="blue-gray"
                                                className="font-normal"
                                            >
                                                {users.email || ''}
                                            </Typography>
                                        </td>
                                        <td className={classes}>
                                            <Typography
                                                variant="small"
                                                color="blue-gray"
                                                className="font-normal"
                                            >
                                                {users.full_name || ''}
                                            </Typography>
                                        </td>
                                        <td className={classes}>
                                            <Typography
                                                variant="small"
                                                color="blue-gray"
                                                className="font-normal"
                                            >
                                                {users.telephone || ''}
                                            </Typography>
                                        </td>

                                        <td className={classes}>
                                            <Tooltip
                                                className=" bg-white shadow-gray-400/10 shadow-xl border p-4"
                                                content={
                                                    <UserAddress user_id={users.user_id} />
                                                }
                                                animate={{
                                                    mount: { scale: 1, y: 0 },
                                                    unmount: { scale: 0, y: 25 },
                                                }}
                                            >
                                                <IconButton variant="text">
                                                    <EyeIcon className="h-4 w-4" />
                                                </IconButton>
                                            </Tooltip>
                                        </td>

                                        <td className={classes}>
                                            <EditUser user_id={users.user_id} />
                                        </td>
                                        <td className={classes}>
                                            <DeleteUser user_id={users.user_id} />
                                        </td>
                                    </tr>
                                );
                            }
                        )}
                    </tbody>
                </table>
            </CardBody>
            <CardFooter className="flex items-center justify-between border-t border-blue-gray-50 p-4">
                <div>

                </div>
                <div className="flex items-center mt-2 ">
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
            </CardFooter>
        </div>
    );
}


const DataTab = [
    {
        label: "Customer",
        value: "dashboard",
        icon: Square3Stack3DIcon,
        desc: <FetchAllUser type_id={3} />
    },
    {
        label: "Seller",
        value: "profile",
        icon: UserCircleIcon,
        desc: <FetchAllUser type_id={2} />
    },
    {
        label: "Search",
        value: "search",
        icon: UserCircleIcon,
        desc: <DataSearch />
    },
];



function DataSearch() {

    const [userEmail, setUserEmail] = useState("")
    const [dataSearch, setDataSearch] = useState([]);

    useEffect(() => {
        const fetchData = async () => {
            if (userEmail.trim() !== "") {
                let res = await searchUser(userEmail);
                setDataSearch(res.data);
            } else {
                setDataSearch([]);
            }
        }
        fetchData();
    }, [userEmail]);

    const EditUser = () => {
        return (
            <div className="cursor-pointer flex justify-center hover:bg-blue-gray-50 py-2 rounded-lg">
                <Popover placement="left">
                    <PopoverHandler>
                        <div className=" w-full flex justify-center">
                            <PencilIcon className=" w-4 h-4" />
                        </div>
                    </PopoverHandler>
                    <PopoverContent className="flex flex-col z-10 p-8 gap-4">

                        <div className=" flex flex-col gap-2">
                            <div>
                                Change Email
                            </div>
                            <div>
                                <Input
                                    label="Input"
                                />
                            </div>
                        </div>

                        <div className=" flex flex-col gap-2">
                            <div>
                                Change Password
                            </div>
                            <div>
                                <Input
                                    label="Input"
                                />
                            </div>
                        </div>

                        <div className=" flex my-8">
                            <div className=" w-[85%]">
                                <Button>Update</Button>
                            </div>
                        </div>
                    </PopoverContent>
                </Popover>
            </div>
        )
    }

    const DeleteUser = ({ user_id }) => {
        const handleDelete = async () => {
            try {
                let res = await userDelete(user_id);
                console.log(res);
            } catch (error) {
                console.error(error);
            }
        }

        return (
            <div onClick={handleDelete} className="cursor-pointer flex justify-center hover:bg-blue-gray-50 py-2 rounded-lg " >
                <TrashIcon className="w-4 h-4 " />
            </div>
        )
    }


    const UserAddress = ({ user_id }) => {
        const [data, setData] = useState([]);

        useEffect(() => {
            const fetchData = async () => {
                try {
                    let res = await userAddress(user_id);
                    setData(res.data);
                } catch (error) {
                    console.error(error);
                }
            }
            fetchData();
        }, []);

        return (
            <div className="w-80 font-normal text-base leading-6 text-gray-800">
                {
                    data && data.length > 0 && data.map((item, index) => (
                        <div key={index}>
                            <Typography className=" font-semibold">
                                Address {index + 1}
                            </Typography>
                            <Typography
                                variant="small"
                                className="font-normal opacity-80"
                            >
                                {item.country}, {item.province}, {item.district}, {item.commune}, {item.street}, {item.number}
                            </Typography>
                        </div>
                    ))
                }
            </div>
        );
    };

    const handleInputChange = (e) => {
        setUserEmail(e.target.value);
    }

    const handleMouseOut = () => {
        setUserEmail(""); 
    }
    return (
        <div>
            <div className="mt-2">
                <Input
                    label="Search"
                    icon={<MagnifyingGlassIcon className="h-5 w-5" />}
                    value={userEmail}
                    onChange={handleInputChange}
                    onMouseDown={handleMouseOut} 
                />
            </div>
            <CardBody className="px-4">
                <table className=" w-full min-w-max table-auto text-left">
                    <thead>
                        <tr>
                            {TABLE_HEAD.map((head, index) => (
                                <th
                                    key={head}
                                    className="cursor-pointer w-fit border-y border-blue-gray-100 bg-blue-gray-50/50 p-4 transition-colors hover:bg-blue-gray-50"
                                >
                                    <Typography
                                        variant="small"
                                        color="blue-gray"
                                        className="flex items-center justify-between font-normal leading-none opacity-70"
                                    >
                                        {head}{" "}
                                        {index !== TABLE_HEAD.length - 1 && (
                                            <ChevronUpDownIcon
                                                strokeWidth={2}
                                                className="h-4 w-4"
                                            />
                                        )}
                                    </Typography>
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {dataSearch.map(
                            (users, index) => {
                                const isLast = index === dataSearch.length - 1;
                                const classes = isLast
                                    ? "p-4"
                                    : "p-4 border-b border-blue-gray-50";

                                return (
                                    <tr key={index}>
                                        <td className={classes}>
                                            <div className="flex items-center gap-3">
                                                <div className="flex flex-col">
                                                    <img className=" w-10 h-10 rounded-full object-cover" src={`../../../src/assets/shop/${users.avt_image}`} alt="" />
                                                </div>
                                            </div>
                                        </td>
                                        <td className={classes}>
                                            <div className="flex flex-col">
                                                <Typography
                                                    variant="small"
                                                    color="blue-gray"
                                                    className="font-normal"
                                                >
                                                    {users.user_id || ''}
                                                </Typography>
                                            </div>
                                        </td>
                                        <td className={classes}>
                                            <div className="w-max">
                                                <Typography
                                                    variant="small"
                                                    color="blue-gray"
                                                    className="font-normal"
                                                >
                                                    {users.username || ''}
                                                </Typography>
                                            </div>
                                        </td>
                                        <td className={classes}>
                                            <Typography
                                                variant="small"
                                                color="blue-gray"
                                                className="font-normal"
                                            >
                                                {users.email || ''}
                                            </Typography>
                                        </td>
                                        <td className={classes}>
                                            <Typography
                                                variant="small"
                                                color="blue-gray"
                                                className="font-normal"
                                            >
                                                {users.full_name || ''}
                                            </Typography>
                                        </td>
                                        <td className={classes}>
                                            <Typography
                                                variant="small"
                                                color="blue-gray"
                                                className="font-normal"
                                            >
                                                {users.telephone || ''}
                                            </Typography>
                                        </td>

                                        <td className={classes}>
                                            <Tooltip
                                                className=" bg-white shadow-gray-400/10 shadow-xl border p-4"
                                                content={
                                                    <UserAddress user_id={users.user_id} />
                                                }
                                                animate={{
                                                    mount: { scale: 1, y: 0 },
                                                    unmount: { scale: 0, y: 25 },
                                                }}
                                            >
                                                <IconButton variant="text">
                                                    <EyeIcon className="h-4 w-4" />
                                                </IconButton>
                                            </Tooltip>
                                        </td>

                                        <td className={classes}>
                                            <EditUser user_id={users.user_id} />
                                        </td>
                                        <td className={classes}>
                                            <DeleteUser user_id={users.user_id} />
                                        </td>
                                    </tr>
                                );
                            }
                        )}
                    </tbody>
                </table>
            </CardBody>

        </div>
    );
}

export function UserList() {
    return (
        <Card className=" h-[98%] w-full p-4">
            <Tabs value="dashboard">
                <TabsHeader>
                    {DataTab.map(({ label, value, icon }) => (
                        <Tab key={value} value={value}>
                            <div className="flex items-center gap-2">
                                {React.createElement(icon, { className: "w-5 h-5" })}
                                {label}
                            </div>
                        </Tab>
                    ))}
                </TabsHeader>
                <TabsBody className="">
                    {DataTab.map(({ value, desc }) => (
                        <TabPanel key={value} value={value}>
                            {desc}
                        </TabPanel>
                    ))}
                </TabsBody>
            </Tabs>
        </Card>
    );
}

