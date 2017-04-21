cmake_minimum_required(VERSION 3.0.2)


PROJECT(vc++filt)


include_directories(${CMAKE_SOURCE_DIR}/../../include)


ADD_EXECUTABLE(
	${PROJECT_NAME}

	source.cpp
)

target_link_libraries(
	${PROJECT_NAME}
	Dbghelp.lib
)
